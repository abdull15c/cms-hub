<div class="container py-5">
    <h2 class="text-light">Write Post</h2>
    
    <div class="glass-card p-4">
        <form action="<?= BASE_URL ?>/admin/blog/store" method="POST">
            <?= \Src\Core\Csrf::field() ?>
            
            <!-- AI TOOLBAR -->
            <div class="p-3 mb-4 border border-info rounded bg-black bg-opacity-25 d-flex gap-2 align-items-center">
                <i class="fa-solid fa-robot text-info fa-lg"></i>
                <select id="ai_lang" class="form-select bg-dark text-light border-secondary w-auto">
                    <option value="ru">🇷🇺 RU</option>
                    <option value="en">🇺🇸 EN</option>
                </select>
                <button type="button" class="btn btn-cyber" onclick="generatePost(this)">
                    <i class="fa-solid fa-pen-nib"></i> AI Write Article
                </button>
                <small class="text-secondary ms-2">Enter title first!</small>
            </div>

            <div class="mb-3">
                <label class="text-secondary">Title</label>
                <input type="text" name="title" id="p_title" class="form-control bg-dark text-light border-secondary" required>
            </div>
            
            <div class="mb-3">
                <label class="text-secondary">Content (HTML allowed)</label>
                <textarea name="content" id="p_content" rows="15" class="form-control bg-dark text-light border-secondary" required></textarea>
            </div>
            
            <button class="btn btn-cyber btn-lg px-5">Publish</button>
        </form>
    </div>
</div>

<script>
async function generatePost(btn) {
    const topic = document.getElementById('p_title').value;
    const lang = document.getElementById('ai_lang').value;
    // Find CSRF (hidden input)
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;

    if(!topic) { alert('Please enter a Title (Topic) first.'); return; }

    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Writing...';
    btn.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/admin/blog/ai_generate', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({topic: topic, lang: lang, csrf_token: csrfToken})
        });
        
        const text = await res.text();
        try {
            const json = JSON.parse(text);
            if(json.status === 'success') {
                // Append content
                const editor = document.getElementById('p_content');
                editor.value = json.content; 
            } else {
                alert('AI Error: ' + json.error);
            }
        } catch(e) {
            console.error(text);
            alert('Server Error. Check console.');
        }

    } catch(e) {
        alert('Network Error');
    }

    btn.innerHTML = originalText;
    btn.disabled = false;
}
</script>