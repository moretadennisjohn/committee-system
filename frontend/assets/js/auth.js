function getBackendURL() {
    var path = window.location.pathname;
    var idx = path.search(/\/(frontend\/)?pages\//);
    var base = '';
    if (idx !== -1) {
        base = path.substring(0, idx);
    } else if (path.indexOf('.') !== -1) {
        base = path.substring(0, path.lastIndexOf('/'));
    } else {
        base = path.replace(/\/$/, '');
    }
    return window.location.origin + base + '/backend/api';
}
var BACKEND = getBackendURL();

async function fetchJSON(url) {
    try {
        const res = await fetch(url);
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            const start = text.indexOf('[');
            const start2 = text.indexOf('{');
            if (start !== -1) return JSON.parse(text.substring(start));
            if (start2 !== -1) return JSON.parse(text.substring(start2));
            return [];
        }
    } catch(e) {
        console.error('fetchJSON error:', url, e);
        return [];
    }
}

async function postJSON(url, data) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            const start = text.indexOf('{');
            if (start !== -1) return JSON.parse(text.substring(start));
            return {success: false};
        }
    } catch(e) {
        console.error('postJSON error:', e);
        return {success: false};
    }
}

async function deleteJSON(url, data) {
    try {
        const res = await fetch(url, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const text = await res.text();
        try { return JSON.parse(text); }
        catch(e) { return {success: true}; }
    } catch(e) {
        return {success: false};
    }
}

async function callGemini(prompt) {
    try {
        const res = await fetch(BACKEND + '/ai/generate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ prompt: prompt })
        });
        const data = await res.json();
        if (data.error) {
            console.error('Gemini error:', data.error.message || data.error);
            return '';
        }
        return data.candidates?.[0]?.content?.parts?.[0]?.text || '';
    } catch(e) {
        console.error('Gemini error:', e);
        return '';
    }
}

function populateSelect(elementId, items, valueKey, labelKey, placeholder) {
    const select = document.getElementById(elementId);
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>` +
        items.map(item => `<option value="${item[valueKey]}">${item[labelKey]}</option>`).join('');
}