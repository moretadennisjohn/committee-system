const BACKEND = 'http://localhost/committee-management/backend/api';
const GEMINI_KEY = 'AQ.Ab8RN6LxwEHD8ygzbNkyJ4rUVl29qTF4JtKbq3YmLhCPIKhKkA';

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
        const res = await fetch(
            https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_KEY},
            {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    contents: [{parts: [{text: prompt}]}]
                })
            }
        );
        const data = await res.json();
        return data.candidates?.[0]?.content?.parts?.[0]?.text || '';
    } catch(e) {
        console.error('Gemini error:', e);
        return '';
    }
}

function populateSelect(elementId, items, valueKey, labelKey, placeholder) {
    const select = document.getElementById(elementId);
    if (!select) return;
    select.innerHTML = <option value="">${placeholder}</option> +
        items.map(item => <option value="${item[valueKey]}">${item[labelKey]}</option>).join('');
}