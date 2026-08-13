document.addEventListener('DOMContentLoaded', () => {
    // Sync text inputs to resume elements
    // Sync text inputs to resume elements
    const bindInput = (inputId, outputId) => {
        const inputEl = document.getElementById(inputId);
        const outputEl = document.getElementById(outputId);
        
        if (inputEl && outputEl) {
            // Make the output element editable directly
            outputEl.setAttribute('contenteditable', 'true');
            outputEl.setAttribute('spellcheck', 'false');

            inputEl.addEventListener('input', (e) => {
                outputEl.innerText = e.target.value;
            });
            
            // Sync changes from the preview back to the input form
            outputEl.addEventListener('input', (e) => {
                inputEl.value = e.target.innerText;
            });
        }
    };

    // Sync textarea to bulleted lists
    const bindList = (inputId, outputId) => {
        const inputEl = document.getElementById(inputId);
        const outputEl = document.getElementById(outputId);

        if (inputEl && outputEl) {
            // Make the list editable directly
            outputEl.setAttribute('contenteditable', 'true');
            outputEl.setAttribute('spellcheck', 'false');

            inputEl.addEventListener('input', (e) => {
                const lines = e.target.value.split('\n').filter(l => l.trim() !== '');
                outputEl.innerHTML = lines.map(line => `<li>${line}</li>`).join('');
            });
            
            // Sync changes from the list back to the textarea
            outputEl.addEventListener('input', (e) => {
                const lis = outputEl.querySelectorAll('li');
                if (lis.length > 0) {
                    inputEl.value = Array.from(lis).map(li => li.innerText).join('\n');
                } else {
                    inputEl.value = outputEl.innerText;
                }
            });
        }
    };

    // Make standalone headers editable too
    document.querySelectorAll('#resume-content h2').forEach(el => {
        el.setAttribute('contenteditable', 'true');
        el.setAttribute('spellcheck', 'false');
    });


    // Header
    bindInput('in-name', 'out-name');
    bindInput('in-contact', 'out-contact');
    bindInput('in-summary', 'out-summary');

    // Education 1
    bindInput('in-e1-inst', 'out-e1-inst');
    bindInput('in-e1-loc', 'out-e1-loc');
    bindInput('in-e1-deg', 'out-e1-deg');
    bindInput('in-e1-date', 'out-e1-date');
    bindInput('in-e1-det', 'out-e1-det');

    // Education 2
    bindInput('in-e2-inst', 'out-e2-inst');
    bindInput('in-e2-loc', 'out-e2-loc');
    bindInput('in-e2-deg', 'out-e2-deg');
    bindInput('in-e2-date', 'out-e2-date');

    // Work Experience 1
    bindInput('in-w1-comp', 'out-w1-comp');
    bindInput('in-w1-loc', 'out-w1-loc');
    bindInput('in-w1-role', 'out-w1-role');
    bindInput('in-w1-date', 'out-w1-date');
    bindList('in-w1-desc', 'out-w1-desc');

    // Work Experience 2
    bindInput('in-w2-comp', 'out-w2-comp');
    bindInput('in-w2-loc', 'out-w2-loc');
    bindInput('in-w2-role', 'out-w2-role');
    bindInput('in-w2-date', 'out-w2-date');
    bindList('in-w2-desc', 'out-w2-desc');

    // Work Experience 3
    bindInput('in-w3-comp', 'out-w3-comp');
    bindInput('in-w3-loc', 'out-w3-loc');
    bindInput('in-w3-role', 'out-w3-role');
    bindInput('in-w3-date', 'out-w3-date');
    bindList('in-w3-desc', 'out-w3-desc');

    // Volunteer Experience
    bindInput('in-v1-org', 'out-v1-org');
    bindInput('in-v1-loc', 'out-v1-loc');
    bindInput('in-v1-role', 'out-v1-role');
    bindInput('in-v1-date', 'out-v1-date');
    bindList('in-v1-desc', 'out-v1-desc');

    // Native PDF Print
    const downloadBtn = document.getElementById('download-btn');
    
    downloadBtn.addEventListener('click', () => {
        // We use window.print() instead of html2pdf for two critical reasons:
        // 1. html2pdf generates an image of the resume. Image-based resumes cannot be read by ATS (Applicant Tracking Systems)!
        // 2. html2canvas has bugs with flexbox and scrolling containers preventing the download.
        // Native print creates a perfect, text-selectable PDF.
        alert("To create a perfect, text-selectable PDF (essential for job applications!), we will use your browser's native print screen. \n\nPlease select 'Save as PDF' from the Destination dropdown.");
        window.print();
    });
});
