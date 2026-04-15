<?php
$mode = $mode ?? 'create';
$isEdit = $mode === 'edit';
$actionUrl = $isEdit ? BASE_URL . '/admin/product/update/' . (int)$product['id'] : BASE_URL . '/admin/product/store';
$pageTitle = $isEdit ? 'Edit Product' : 'Add New Product';
$saleEndValue = !empty($product['sale_end']) ? date('Y-m-d\TH:i', strtotime((string)$product['sale_end'])) : '';
$currentStatus = $product['status'] ?? 'published';
$ru = $translations['ru'] ?? ['title' => '', 'description' => '', 'meta_title' => '', 'meta_desc' => '', 'meta_keywords' => ''];
$en = $translations['en'] ?? ['title' => '', 'description' => '', 'meta_title' => '', 'meta_desc' => '', 'meta_keywords' => ''];
?>

<style>
    .product-builder-page {
        --panel-bg: rgba(20, 22, 37, 0.88);
        --panel-border: rgba(255, 255, 255, 0.08);
        --panel-muted: #94a3b8;
        --panel-accent: #00f2ea;
        --panel-accent-2: #8b5cf6;
    }

    .builder-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
        gap: 24px;
    }

    .builder-panel {
        background: var(--panel-bg);
        border: 1px solid var(--panel-border);
        border-radius: 24px;
        backdrop-filter: blur(20px);
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
    }

    .builder-section + .builder-section {
        margin-top: 24px;
    }

    .builder-kicker {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: #f59e0b;
        font-weight: 700;
    }

    .builder-lang-tab {
        border: 1px solid var(--panel-border);
        color: #cbd5e1;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.03);
    }

    .builder-lang-tab.active {
        background: linear-gradient(135deg, rgba(0, 242, 234, 0.2), rgba(139, 92, 246, 0.28));
        color: white;
        border-color: rgba(0, 242, 234, 0.35);
    }

    .builder-lang-pane {
        display: none;
    }

    .builder-lang-pane.active {
        display: block;
    }

    .builder-upload-card {
        border: 1px dashed rgba(255, 255, 255, 0.15);
        border-radius: 18px;
        padding: 18px;
        background: rgba(255, 255, 255, 0.02);
    }

    .builder-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
        gap: 12px;
    }

    .builder-preview-grid img {
        width: 100%;
        height: 92px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .builder-sticky {
        position: sticky;
        top: 96px;
    }

    .builder-metric {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 18px;
        padding: 16px;
    }

    .builder-checklist li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .builder-checklist li:last-child {
        border-bottom: 0;
    }

    .builder-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        color: #cbd5e1;
        font-size: 0.85rem;
    }

    .builder-source-result {
        white-space: pre-wrap;
        font-size: 0.88rem;
        color: var(--panel-muted);
    }

    @media (max-width: 1199px) {
        .builder-shell {
            grid-template-columns: 1fr;
        }

        .builder-sticky {
            position: static;
        }
    }
</style>

<div class="container-fluid py-4 product-builder-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="builder-kicker"><?= $isEdit ? $t('builder_editing_kicker', 'Product Editing Studio') : $t('builder_publishing_kicker', 'Product Publishing Studio') ?></div>
            <h1 class="text-light fw-bold mb-1"><?= htmlspecialchars($isEdit ? $t('builder_edit_title', 'Edit Product') : $t('builder_create_title', 'Add New Product')) ?><?= $isEdit ? ': ' . htmlspecialchars($product['title'] ?? '') : '' ?></h1>
            <p class="text-secondary mb-0"><?= $t('builder_subtitle', 'One product, two native language versions, source-aware AI analysis, richer publishing controls.') ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/admin/dashboard" class="btn btn-outline-light rounded-pill px-4"><?= $t('builder_back_dashboard', 'Back to Dashboard') ?></a>
        </div>
    </div>

    <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" id="productBuilderForm" novalidate>
        <?= \Src\Core\Csrf::field() ?>
        <div class="builder-shell align-items-start">
            <div>
                <div class="builder-panel p-4 builder-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h4 class="text-light fw-bold mb-1"><i class="fa-solid fa-sparkles text-info me-2"></i><?= $t('builder_source_analysis', 'Source Analysis') ?></h4>
                            <p class="text-secondary mb-0"><?= $t('builder_source_subtitle', 'Paste code or README, or upload a ZIP with source files. AI will infer what the project is and generate separate RU and EN copy.') ?></p>
                        </div>
                        <button type="button" class="btn btn-info text-dark fw-semibold rounded-pill px-4" id="analyzeSourceBtn">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i><?= $t('builder_analyze_fill', 'Analyze and Fill') ?>
                        </button>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <label class="form-label text-light fw-semibold"><?= $t('builder_source_text', 'Source Text / README / Notes') ?></label>
                            <textarea class="form-control bg-dark text-light border-secondary" rows="11" name="source_text" id="sourceText" placeholder="<?= htmlspecialchars($t('builder_source_text_placeholder', 'Paste README, source snippets, feature notes, setup docs, or a code sample.')) ?>"></textarea>
                        </div>
                        <div class="col-lg-5">
                            <div class="builder-upload-card mb-3">
                                <label class="form-label text-light fw-semibold"><?= $t('builder_source_zip', 'Source ZIP for analysis') ?></label>
                                <input type="file" class="form-control bg-dark text-light border-secondary" name="source_archive" id="sourceArchive" accept=".zip">
                                <div class="small text-secondary mt-2"><?= $t('builder_source_zip_hint', 'Temporary use only. ZIP is analyzed to understand the project and is not attached to the product.') ?></div>
                            </div>
                            <div class="builder-upload-card mb-3">
                                <label class="form-label text-light fw-semibold"><?= $t('builder_repo_url', 'Repository URL') ?></label>
                                <input type="url" class="form-control bg-dark text-light border-secondary" name="repo_url" id="repoUrl" placeholder="<?= htmlspecialchars($t('builder_repo_url_placeholder', 'https://github.com/example/project')) ?>">
                            </div>
                            <div class="builder-upload-card">
                                <div class="small text-uppercase text-warning fw-bold mb-2"><?= $t('builder_ai_extracts', 'What AI extracts') ?></div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="builder-chip"><?= $t('builder_extract_project_type', 'Project type') ?></span>
                                    <span class="builder-chip"><?= $t('builder_extract_features', 'Core features') ?></span>
                                    <span class="builder-chip"><?= $t('builder_extract_audience', 'Audience') ?></span>
                                    <span class="builder-chip"><?= $t('builder_extract_stack', 'Stack') ?></span>
                                    <span class="builder-chip"><?= $t('builder_extract_seo', 'SEO themes') ?></span>
                                    <span class="builder-chip">RU + EN copy</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-4 border border-secondary border-opacity-25 bg-black bg-opacity-25" id="analysisResultBox">
                        <div class="text-secondary small"><?= $t('builder_no_analysis', 'No source analysis yet.') ?></div>
                    </div>
                </div>

                <div class="builder-panel p-4 builder-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h4 class="text-light fw-bold mb-1"><i class="fa-solid fa-globe text-info me-2"></i><?= $t('builder_localized_content', 'Localized Product Content') ?></h4>
                            <p class="text-secondary mb-0"><?= $t('builder_localized_subtitle', 'Write once, publish two native versions. RU and EN are stored separately, not as a single translation blob.') ?></p>
                        </div>
                        <div class="btn-group" role="group" id="langTabs">
                            <button type="button" class="btn builder-lang-tab active" data-lang="ru">RU Content</button>
                            <button type="button" class="btn builder-lang-tab" data-lang="en">EN Content</button>
                        </div>
                    </div>

                    <?php foreach (['ru' => $ru, 'en' => $en] as $langCode => $translation): ?>
                        <div class="builder-lang-pane <?= $langCode === 'ru' ? 'active' : '' ?>" data-lang-pane="<?= $langCode ?>">
                            <div class="row g-4">
                                <div class="col-xl-8">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label text-light fw-semibold mb-0"><?= strtoupper($langCode) ?> Title</label>
                                            <span class="small text-secondary" data-title-count="<?= $langCode ?>">0 chars</span>
                                        </div>
                                        <input type="text" class="form-control form-control-lg bg-dark text-light border-secondary" name="translations[<?= $langCode ?>][title]" data-title-input="<?= $langCode ?>" value="<?= htmlspecialchars($translation['title'] ?? '') ?>" placeholder="<?= $langCode === 'ru' ? 'Название товара на русском' : 'Product title in English' ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                            <label class="form-label text-light fw-semibold mb-0"><?= strtoupper($langCode) ?> Description</label>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" data-action="code" data-lang="<?= $langCode ?>"><i class="fa-solid fa-code me-1"></i>Review Source</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-action="marketing" data-lang="<?= $langCode ?>"><i class="fa-solid fa-fire me-1"></i>Polish Copy</button>
                                            </div>
                                        </div>
                                        <textarea class="form-control bg-dark text-light border-secondary" rows="12" name="translations[<?= $langCode ?>][description]" data-description-input="<?= $langCode ?>" placeholder="<?= $langCode === 'ru' ? 'Описание на русском' : 'Description in English' ?>"><?= htmlspecialchars($translation['description'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="col-xl-4">
                                    <div class="builder-upload-card h-100">
                                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                            <h5 class="text-light fw-semibold mb-0">SEO / <?= strtoupper($langCode) ?></h5>
                                            <button type="button" class="btn btn-sm btn-info text-dark rounded-pill px-3" data-action="seo" data-lang="<?= $langCode ?>">
                                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate
                                            </button>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label text-secondary small">Meta Title</label>
                                            <input type="text" class="form-control bg-dark text-light border-secondary" name="translations[<?= $langCode ?>][meta_title]" data-meta-title-input="<?= $langCode ?>" value="<?= htmlspecialchars($translation['meta_title'] ?? '') ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label text-secondary small">Meta Description</label>
                                            <textarea class="form-control bg-dark text-light border-secondary" rows="4" name="translations[<?= $langCode ?>][meta_desc]" data-meta-desc-input="<?= $langCode ?>"><?= htmlspecialchars($translation['meta_desc'] ?? '') ?></textarea>
                                        </div>

                                        <div>
                                            <label class="form-label text-secondary small">Keywords</label>
                                            <input type="text" class="form-control bg-dark text-light border-secondary" name="translations[<?= $langCode ?>][meta_keywords]" data-meta-keywords-input="<?= $langCode ?>" value="<?= htmlspecialchars($translation['meta_keywords'] ?? '') ?>" placeholder="keyword1, keyword2, keyword3">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="builder-panel p-4 builder-section">
                    <h4 class="text-light fw-bold mb-4"><i class="fa-solid fa-box-open text-info me-2"></i>Product Setup</h4>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label text-light fw-semibold mb-0"><?= $t('builder_category', 'Category') ?></label>
                                <a href="<?= BASE_URL ?>/admin/categories" class="small text-info text-decoration-none"><?= $t('builder_manage_categories', 'Manage categories') ?></a>
                            </div>
                            <select name="category_id" id="categorySelect" class="form-select bg-dark text-light border-secondary" required>
                                <option value=""><?= $t('builder_choose_category', 'Choose category') ?></option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (string)($c['id']) === (string)($product['category_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-light fw-semibold"><?= $t('builder_regular_price', 'Regular Price ($)') ?></label>
                            <input type="number" step="0.01" min="0.01" name="price" id="priceInput" value="<?= htmlspecialchars((string)($product['price'] ?? '')) ?>" class="form-control bg-dark text-light border-secondary" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch fs-6 mb-2">
                                <input class="form-check-input" type="checkbox" name="has_license" value="1" id="licCheck" <?= !empty($product['has_license']) ? 'checked' : '' ?>>
                                <label class="form-check-label text-warning fw-semibold" for="licCheck"><?= $t('builder_generate_license', 'Generate License Key') ?></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light fw-semibold"><?= $t('builder_sale_price', 'Flash Sale Price ($)') ?></label>
                            <input type="number" step="0.01" min="0" name="sale_price" id="salePriceInput" value="<?= htmlspecialchars((string)($product['sale_price'] ?? '')) ?>" class="form-control bg-dark text-light border-secondary" placeholder="<?= htmlspecialchars($t('builder_sale_price_placeholder', 'Optional discount price')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light fw-semibold"><?= $t('builder_sale_end', 'Flash Sale End') ?></label>
                            <input type="datetime-local" name="sale_end" id="saleEndInput" value="<?= htmlspecialchars($saleEndValue) ?>" class="form-control bg-dark text-light border-secondary">
                        </div>
                    </div>
                </div>

                <div class="builder-panel p-4 builder-section">
                    <h4 class="text-light fw-bold mb-4"><i class="fa-solid fa-paperclip text-info me-2"></i><?= $t('builder_files_media', 'Files and Media') ?></h4>
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="builder-upload-card h-100">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                                    <div>
                                        <div class="text-light fw-semibold"><?= $t('builder_main_archive', 'Main Product Archive') ?></div>
                                        <div class="small text-secondary"><?= $t('builder_main_archive_hint', 'The file buyers receive after purchase.') ?></div>
                                    </div>
                                    <?php if ($isEdit && !empty($product['file_path'])): ?>
                                        <span class="badge bg-secondary-subtle text-light border border-secondary border-opacity-25"><?= $t('builder_current_file', 'Current') ?>: <?= htmlspecialchars($product['file_path']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="file" id="productFileInput" class="form-control bg-dark text-light border-secondary" <?= $isEdit ? '' : 'required' ?> accept=".zip,.rar,.7z">
                                <div class="small text-secondary mt-3" id="productFileInfo"><?= $isEdit ? $t('builder_keep_archive', 'Leave empty to keep current archive.') : $t('builder_archive_types', 'ZIP, RAR or 7Z only.') ?></div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="builder-upload-card h-100">
                                <div class="text-light fw-semibold mb-1"><?= $t('builder_gallery', 'Gallery Screenshots') ?></div>
                                <div class="small text-secondary mb-3"><?= $t('builder_gallery_hint', 'Upload multiple previews. First new image becomes the main image if none exists.') ?></div>
                                <input type="file" name="images[]" id="productImagesInput" multiple accept="image/*" class="form-control bg-dark text-light border-secondary">
                                <div class="builder-preview-grid mt-3" id="imagePreviewGrid"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="builder-panel p-4 builder-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h4 class="text-light fw-bold mb-1"><i class="fa-solid fa-rocket text-info me-2"></i><?= $t('builder_publishing_options', 'Publishing Options') ?></h4>
                            <p class="text-secondary mb-0"><?= $t('builder_publishing_options_hint', 'Optional automation for blog content and richer launch setup.') ?></p>
                        </div>
                    </div>
                    <div class="row g-4 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input" type="checkbox" name="auto_blog" value="1" id="autoBlogCheck" checked>
                                <label class="form-check-label text-info fw-semibold" for="autoBlogCheck"><?= $t('builder_auto_blog', 'Auto-write blog post after publish') ?></label>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label class="form-label text-light fw-semibold"><?= $t('builder_blog_language', 'Blog Language') ?></label>
                            <select name="ai_lang" class="form-select bg-dark text-light border-secondary">
                                <option value="ru"><?= $t('lang_ru', 'Russian') ?></option>
                                <option value="en"><?= $t('lang_en', 'English') ?></option>
                            </select>
                        </div>
                        <div class="col-lg-4 text-lg-end d-flex gap-2 justify-content-lg-end">
                            <button type="submit" class="btn btn-lg btn-outline-warning fw-bold rounded-pill px-4" data-status="draft">
                                <i class="fa-solid fa-box-archive me-2"></i><?= $t('builder_save_draft', 'Save Draft') ?>
                            </button>
                            <button type="submit" class="btn btn-lg btn-info text-dark fw-bold rounded-pill px-5" id="publishBtn" data-status="published">
                                <i class="fa-solid fa-cloud-arrow-up me-2"></i><?= $isEdit ? $t('builder_save_publish', 'Save & Publish') : $t('builder_publish', 'Publish Product') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="builder-sticky">
                <div class="builder-panel p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-light fw-bold mb-0"><i class="fa-solid fa-eye text-info me-2"></i>Publishing Summary</h5>
                        <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill px-3" id="completionBadge">0 / 5 ready</span>
                    </div>

                    <div class="builder-metric mb-3">
                        <div class="text-secondary small mb-1"><?= $t('builder_summary_title', 'Primary visible title') ?></div>
                        <div class="text-light fw-semibold" id="summaryTitle"><?= $t('builder_no_title', 'No title yet') ?></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="builder-metric h-100">
                                <div class="text-secondary small mb-1"><?= $t('builder_ru_status', 'RU status') ?></div>
                                <div class="text-light fw-semibold" id="summaryRuStatus"><?= $t('builder_missing', 'Missing') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="builder-metric h-100">
                                <div class="text-secondary small mb-1"><?= $t('builder_en_status', 'EN status') ?></div>
                                <div class="text-light fw-semibold" id="summaryEnStatus"><?= $t('builder_missing', 'Missing') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="builder-metric mb-3">
                        <div class="text-secondary small mb-1"><?= $t('builder_price_preview', 'Price Preview') ?></div>
                        <div class="text-light fw-semibold" id="summaryPrice"><?= $t('builder_set_price', 'Set price') ?></div>
                    </div>

                    <div class="builder-metric mb-3">
                        <div class="text-secondary small mb-1">Live Card Preview</div>
                        <div class="rounded-4 border border-secondary border-opacity-25 p-3 bg-black bg-opacity-25">
                            <div class="small text-secondary mb-1" id="previewCategory"><?= $t('builder_category_pending', 'Category pending') ?></div>
                            <div class="text-light fw-bold mb-2" id="previewCardTitle"><?= $t('builder_no_title', 'No title yet') ?></div>
                            <div class="small text-secondary mb-3" id="previewCardExcerpt"><?= $t('builder_description_preview', 'Description preview will appear here.') ?></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-info fw-semibold" id="previewCardPrice">$0.00</span>
                                <span class="badge rounded-pill <?= $currentStatus === 'draft' ? 'bg-warning text-dark' : 'bg-success' ?>" id="previewStatusBadge"><?= htmlspecialchars($currentStatus === 'draft' ? $t('products_draft', 'Draft') : $t('products_published_one', 'Published')) ?></span>
                            </div>
                        </div>
                    </div>

                    <ul class="list-unstyled builder-checklist mb-0" id="publishChecklist">
                        <li><span class="text-secondary">Category selected</span><span id="checkCategory" class="text-danger">Missing</span></li>
                        <li><span class="text-secondary">RU content ready</span><span id="checkRu" class="text-danger">Missing</span></li>
                        <li><span class="text-secondary">EN content ready</span><span id="checkEn" class="text-danger">Missing</span></li>
                        <li><span class="text-secondary">Price valid</span><span id="checkPrice" class="text-danger">Missing</span></li>
                        <li><span class="text-secondary">Main archive attached</span><span id="checkFile" class="text-danger">Missing</span></li>
                    </ul>
                </div>

                <div class="builder-panel p-4">
                    <h5 class="text-light fw-bold mb-3"><i class="fa-solid fa-lightbulb text-warning me-2"></i>Source Intelligence</h5>
                    <div class="small text-secondary mb-2" id="categoryConfidenceLine"><?= $t('builder_category_confidence', 'Category confidence') ?>: n/a</div>
                    <div id="profileInsights" class="builder-source-result"><?= $t('builder_profile_hint', 'Upload a source ZIP or paste a README to let AI extract project type, features, audience and marketable value.') ?></div>
                </div>
            </div>
        </div>
        <input type="hidden" name="status" id="productStatusInput" value="<?= htmlspecialchars($currentStatus) ?>">
    </form>
</div>

<script nonce="<?= CSP_NONCE ?>">
(() => {
    const i18n = {
        noTitle: <?= json_encode($t('builder_no_title', 'No title yet'), JSON_UNESCAPED_UNICODE) ?>,
        missing: <?= json_encode($t('builder_missing', 'Missing'), JSON_UNESCAPED_UNICODE) ?>,
        ready: <?= json_encode($t('builder_ready', 'Ready'), JSON_UNESCAPED_UNICODE) ?>,
        valid: <?= json_encode($t('builder_valid', 'Valid'), JSON_UNESCAPED_UNICODE) ?>,
        fix: <?= json_encode($t('builder_fix', 'Fix'), JSON_UNESCAPED_UNICODE) ?>,
        attach: <?= json_encode($t('builder_attach', 'Attach'), JSON_UNESCAPED_UNICODE) ?>,
        keepCurrent: <?= json_encode($t('builder_keep_current', 'Keeping current'), JSON_UNESCAPED_UNICODE) ?>,
        attached: <?= json_encode($t('builder_attached', 'Attached'), JSON_UNESCAPED_UNICODE) ?>,
        setPrice: <?= json_encode($t('builder_set_price', 'Set price'), JSON_UNESCAPED_UNICODE) ?>,
        categoryPending: <?= json_encode($t('builder_category_pending', 'Category pending'), JSON_UNESCAPED_UNICODE) ?>,
        descriptionPreview: <?= json_encode($t('builder_description_preview', 'Description preview will appear here.'), JSON_UNESCAPED_UNICODE) ?>,
        draft: <?= json_encode($t('products_draft', 'Draft'), JSON_UNESCAPED_UNICODE) ?>,
        published: <?= json_encode($t('products_published_one', 'Published'), JSON_UNESCAPED_UNICODE) ?>,
        source: <?= json_encode($t('builder_source_label', 'Source'), JSON_UNESCAPED_UNICODE) ?>,
        type: <?= json_encode($t('builder_type_label', 'Type'), JSON_UNESCAPED_UNICODE) ?>,
        audience: <?= json_encode($t('builder_audience_label', 'Audience'), JSON_UNESCAPED_UNICODE) ?>,
        categoryConfidence: <?= json_encode($t('builder_category_confidence', 'Category confidence'), JSON_UNESCAPED_UNICODE) ?>,
        keyFeatures: <?= json_encode($t('builder_key_features', 'Key Features'), JSON_UNESCAPED_UNICODE) ?>,
        techStack: <?= json_encode($t('builder_tech_stack', 'Tech Stack'), JSON_UNESCAPED_UNICODE) ?>,
        notDetected: <?= json_encode($t('builder_not_detected', 'Not detected'), JSON_UNESCAPED_UNICODE) ?>,
        aiSourceReview: <?= json_encode($t('builder_ai_source_review', 'AI Source Review'), JSON_UNESCAPED_UNICODE) ?>,
        sourceArchiveSelected: <?= json_encode($t('builder_source_archive_selected', 'Source archive selected'), JSON_UNESCAPED_UNICODE) ?>,
        enterTitleFirst: <?= json_encode($t('builder_enter_title_first', 'Enter title first.'), JSON_UNESCAPED_UNICODE) ?>,
        aiError: <?= json_encode($t('builder_ai_error', 'AI Error'), JSON_UNESCAPED_UNICODE) ?>,
        networkSeo: <?= json_encode($t('builder_network_seo', 'Network error while generating SEO.'), JSON_UNESCAPED_UNICODE) ?>,
        writeDraftFirst: <?= json_encode($t('builder_write_draft_first', 'Write a base draft first.'), JSON_UNESCAPED_UNICODE) ?>,
        networkPolish: <?= json_encode($t('builder_network_polish', 'Network error while polishing the description.'), JSON_UNESCAPED_UNICODE) ?>,
        pasteSourceFirst: <?= json_encode($t('builder_paste_source_first', 'Paste source notes or write a draft first.'), JSON_UNESCAPED_UNICODE) ?>,
        networkReview: <?= json_encode($t('builder_network_review', 'Network error while reviewing the source.'), JSON_UNESCAPED_UNICODE) ?>,
        networkAnalyze: <?= json_encode($t('builder_network_analyze', 'Network error while analyzing the source.'), JSON_UNESCAPED_UNICODE) ?>,
        completeBeforePublish: <?= json_encode($t('builder_complete_before_publish', 'Complete category, RU title, EN title, price and main archive before publishing.'), JSON_UNESCAPED_UNICODE) ?>,
        salePriceLower: <?= json_encode($t('builder_sale_price_lower', 'Sale price must be lower than regular price.'), JSON_UNESCAPED_UNICODE) ?>
    };

    const form = document.getElementById('productBuilderForm');
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    const tabs = document.querySelectorAll('#langTabs [data-lang]');
    const panes = document.querySelectorAll('[data-lang-pane]');
    const imagePreviewGrid = document.getElementById('imagePreviewGrid');
    const productFileInput = document.getElementById('productFileInput');
    const productImagesInput = document.getElementById('productImagesInput');
    const sourceArchive = document.getElementById('sourceArchive');
    const productFileInfo = document.getElementById('productFileInfo');
    const analysisResultBox = document.getElementById('analysisResultBox');
    const profileInsights = document.getElementById('profileInsights');
    const analyzeSourceBtn = document.getElementById('analyzeSourceBtn');
    const storageKey = 'market-product-builder-draft';

    function switchLang(lang) {
        tabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.lang === lang));
        panes.forEach((pane) => pane.classList.toggle('active', pane.dataset.langPane === lang));
    }

    tabs.forEach((tab) => tab.addEventListener('click', () => switchLang(tab.dataset.lang)));

    function setStatus(el, ok, okText = 'Ready', failText = 'Missing') {
        el.textContent = ok ? okText : failText;
        el.className = ok ? 'text-success fw-semibold' : 'text-danger';
    }

    function updateTitleCounts() {
        ['ru', 'en'].forEach((lang) => {
            const input = form.querySelector(`[data-title-input="${lang}"]`);
            const count = form.querySelector(`[data-title-count="${lang}"]`);
            if (input && count) {
                count.textContent = `${input.value.trim().length} chars`;
            }
        });
    }

    function summaryTitle() {
        return form.querySelector('[data-title-input="ru"]').value.trim() || form.querySelector('[data-title-input="en"]').value.trim() || i18n.noTitle;
    }

    function updateSummary() {
        updateTitleCounts();
        const ruTitle = form.querySelector('[data-title-input="ru"]').value.trim();
        const enTitle = form.querySelector('[data-title-input="en"]').value.trim();
        const ruDesc = form.querySelector('[data-description-input="ru"]').value.trim();
        const enDesc = form.querySelector('[data-description-input="en"]').value.trim();
        const categorySelected = document.getElementById('categorySelect').value !== '';
        const price = parseFloat(document.getElementById('priceInput').value || '0');
        const salePriceRaw = document.getElementById('salePriceInput').value;
        const salePrice = salePriceRaw !== '' ? parseFloat(salePriceRaw) : null;
        const fileReady = isEdit || productFileInput.files.length > 0;
        const ruReady = ruTitle !== '' && ruDesc.length >= 30;
        const enReady = enTitle !== '' && enDesc.length >= 30;
        const priceReady = Number.isFinite(price) && price > 0 && (salePrice === null || salePrice < price);
        const saleSuffix = salePrice !== null && Number.isFinite(salePrice) ? ` | Sale ${salePrice.toFixed(2)}` : '';
        const readyCount = [categorySelected, ruReady, enReady, priceReady, fileReady].filter(Boolean).length;
        const categoryText = document.querySelector('#categorySelect option:checked')?.textContent?.trim() || i18n.categoryPending;
        const summaryDesc = (form.querySelector('[data-description-input="ru"]').value.trim() || form.querySelector('[data-description-input="en"]').value.trim() || i18n.descriptionPreview).replace(/<[^>]*>/g, '');
        const statusInput = document.getElementById('productStatusInput').value;

        document.getElementById('summaryTitle').textContent = summaryTitle();
        document.getElementById('summaryPrice').textContent = priceReady ? `$${price.toFixed(2)}${saleSuffix}` : i18n.setPrice;
        document.getElementById('completionBadge').textContent = `${readyCount} / 5 ready`;
        document.getElementById('previewCategory').textContent = categoryText;
        document.getElementById('previewCardTitle').textContent = summaryTitle();
        document.getElementById('previewCardExcerpt').textContent = summaryDesc.slice(0, 140) || i18n.descriptionPreview;
        document.getElementById('previewCardPrice').textContent = priceReady ? `$${price.toFixed(2)}` : '$0.00';
        const badge = document.getElementById('previewStatusBadge');
        badge.textContent = statusInput === 'draft' ? i18n.draft : i18n.published;
        badge.className = `badge rounded-pill ${statusInput === 'draft' ? 'bg-warning text-dark' : 'bg-success'}`;
        setStatus(document.getElementById('summaryRuStatus'), ruReady);
        setStatus(document.getElementById('summaryEnStatus'), enReady);
        setStatus(document.getElementById('checkCategory'), categorySelected);
        setStatus(document.getElementById('checkRu'), ruReady);
        setStatus(document.getElementById('checkEn'), enReady);
        setStatus(document.getElementById('checkPrice'), priceReady, i18n.valid, i18n.fix);
        setStatus(document.getElementById('checkFile'), fileReady, isEdit && productFileInput.files.length === 0 ? i18n.keepCurrent : i18n.attached, i18n.attach);
    }

    function updateFilePreview() {
        if (productFileInput.files.length > 0) {
            const file = productFileInput.files[0];
            productFileInfo.textContent = `${file.name} | ${(file.size / 1024 / 1024).toFixed(2)} MB`;
        } else {
            productFileInfo.textContent = isEdit ? <?= json_encode($t('builder_keep_archive', 'Leave empty to keep current archive.'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode($t('builder_archive_types', 'ZIP, RAR or 7Z only.'), JSON_UNESCAPED_UNICODE) ?>;
        }
        updateSummary();
    }

    function updateImagePreview() {
        imagePreviewGrid.innerHTML = '';
        Array.from(productImagesInput.files).slice(0, 12).forEach((file) => {
            const url = URL.createObjectURL(file);
            const img = document.createElement('img');
            img.src = url;
            img.alt = file.name;
            imagePreviewGrid.appendChild(img);
        });
    }

    function serializeDraft() {
        const payload = {
            category_id: document.getElementById('categorySelect').value,
            price: document.getElementById('priceInput').value,
            sale_price: document.getElementById('salePriceInput').value,
            sale_end: document.getElementById('saleEndInput').value,
            status: document.getElementById('productStatusInput').value,
            has_license: document.getElementById('licCheck').checked,
            source_text: document.getElementById('sourceText').value,
            repo_url: document.getElementById('repoUrl').value,
            translations: {}
        };
        ['ru', 'en'].forEach((lang) => {
            payload.translations[lang] = {
                title: form.querySelector(`[data-title-input="${lang}"]`).value,
                description: form.querySelector(`[data-description-input="${lang}"]`).value,
                meta_title: form.querySelector(`[data-meta-title-input="${lang}"]`).value,
                meta_desc: form.querySelector(`[data-meta-desc-input="${lang}"]`).value,
                meta_keywords: form.querySelector(`[data-meta-keywords-input="${lang}"]`).value,
            };
        });
        return payload;
    }

    function restoreDraft() {
        if (isEdit) {
            return;
        }
        const raw = window.localStorage.getItem(storageKey);
        if (!raw) {
            return;
        }
        try {
            const draft = JSON.parse(raw);
            if (draft.category_id) document.getElementById('categorySelect').value = draft.category_id;
            if (draft.price) document.getElementById('priceInput').value = draft.price;
            if (draft.sale_price) document.getElementById('salePriceInput').value = draft.sale_price;
            if (draft.sale_end) document.getElementById('saleEndInput').value = draft.sale_end;
            if (draft.status) document.getElementById('productStatusInput').value = draft.status;
            document.getElementById('licCheck').checked = !!draft.has_license;
            if (draft.source_text) document.getElementById('sourceText').value = draft.source_text;
            if (draft.repo_url) document.getElementById('repoUrl').value = draft.repo_url;

            ['ru', 'en'].forEach((lang) => {
                const block = draft.translations?.[lang];
                if (!block) return;
                form.querySelector(`[data-title-input="${lang}"]`).value = block.title || '';
                form.querySelector(`[data-description-input="${lang}"]`).value = block.description || '';
                form.querySelector(`[data-meta-title-input="${lang}"]`).value = block.meta_title || '';
                form.querySelector(`[data-meta-desc-input="${lang}"]`).value = block.meta_desc || '';
                form.querySelector(`[data-meta-keywords-input="${lang}"]`).value = block.meta_keywords || '';
            });
        } catch (e) {}
    }

    function saveDraft() {
        if (isEdit) {
            return;
        }
        window.localStorage.setItem(storageKey, JSON.stringify(serializeDraft()));
    }

    function profileToHtml(profile, sourceKind) {
        const esc = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        const features = Array.isArray(profile.key_features) ? profile.key_features : [];
        const stack = Array.isArray(profile.tech_stack) ? profile.tech_stack : [];
        return `
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="builder-chip">${i18n.source}: ${esc(sourceKind)}</span>
                <span class="builder-chip">${i18n.type}: ${esc(profile.project_type || 'software project')}</span>
                <span class="builder-chip">${i18n.audience}: ${esc(profile.audience || 'buyers')}</span>
                <span class="builder-chip">${i18n.categoryConfidence}: ${esc(profile.category_confidence ?? 0)}%</span>
            </div>
            <div class="text-light fw-semibold mb-1">${esc(profile.product_name || 'Detected project')}</div>
            <div class="text-secondary small mb-3">${esc(profile.short_summary || '')}</div>
            <div class="row g-3 small">
                <div class="col-md-6"><div class="builder-upload-card h-100"><div class="text-warning fw-semibold mb-2">${i18n.keyFeatures}</div><div class="text-secondary">${esc(features.join(', ') || i18n.notDetected)}</div></div></div>
                <div class="col-md-6"><div class="builder-upload-card h-100"><div class="text-warning fw-semibold mb-2">${i18n.techStack}</div><div class="text-secondary">${esc(stack.join(', ') || i18n.notDetected)}</div></div></div>
            </div>`;
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        return response.json();
    }

    async function generateSeo(lang) {
        const btn = form.querySelector(`[data-action="seo"][data-lang="${lang}"]`);
        const title = form.querySelector(`[data-title-input="${lang}"]`).value.trim();
        const description = form.querySelector(`[data-description-input="${lang}"]`).value.trim();
        if (!title) {
            alert(i18n.enterTitleFirst);
            return;
        }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Working';
        try {
            const json = await postJson('<?= BASE_URL ?>/admin/product/ai_seo', {title, description, lang, csrf_token: csrfToken});
            if (json.status === 'success' && json.data) {
                form.querySelector(`[data-meta-title-input="${lang}"]`).value = json.data.meta_title || '';
                form.querySelector(`[data-meta-desc-input="${lang}"]`).value = json.data.meta_desc || '';
                form.querySelector(`[data-meta-keywords-input="${lang}"]`).value = json.data.meta_keywords || '';
                saveDraft();
            } else {
                alert(i18n.aiError + ': ' + (json.error || 'Unknown error'));
            }
        } catch (e) {
            alert(i18n.networkSeo);
        }
        btn.disabled = false;
        btn.innerHTML = original;
    }

    async function runMarketing(lang) {
        const btn = form.querySelector(`[data-action="marketing"][data-lang="${lang}"]`);
        const description = form.querySelector(`[data-description-input="${lang}"]`).value.trim();
        if (description.length < 10) {
            alert(i18n.writeDraftFirst);
            return;
        }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Polishing';
        try {
            const json = await postJson('<?= BASE_URL ?>/admin/product/ai_marketing', {text: description, csrf_token: csrfToken});
            if (json.status === 'success') {
                form.querySelector(`[data-description-input="${lang}"]`).value = json.text || description;
                saveDraft();
                updateSummary();
            } else {
                alert(i18n.aiError + ': ' + (json.error || 'Unknown error'));
            }
        } catch (e) {
            alert(i18n.networkPolish);
        }
        btn.disabled = false;
        btn.innerHTML = original;
    }

    async function runCodeReview(lang) {
        const btn = form.querySelector(`[data-action="code"][data-lang="${lang}"]`);
        const sourceText = document.getElementById('sourceText').value.trim() || form.querySelector(`[data-description-input="${lang}"]`).value.trim();
        if (sourceText.length < 10) {
            alert(i18n.pasteSourceFirst);
            return;
        }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Reviewing';
        try {
            const json = await postJson('<?= BASE_URL ?>/admin/product/ai_code', {text: sourceText, csrf_token: csrfToken});
            if (json.status === 'success') {
                analysisResultBox.innerHTML = `<div class="text-light fw-semibold mb-2">${i18n.aiSourceReview}</div><div class="builder-source-result">${(json.review || '').replace(/</g, '&lt;')}</div>`;
            } else {
                alert(i18n.aiError + ': ' + (json.error || 'Unknown error'));
            }
        } catch (e) {
            alert(i18n.networkReview);
        }
        btn.disabled = false;
        btn.innerHTML = original;
    }

    async function analyzeSource() {
        const original = analyzeSourceBtn.innerHTML;
        analyzeSourceBtn.disabled = true;
        analyzeSourceBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Analyzing';

        const payload = new FormData();
        payload.append('csrf_token', csrfToken);
        payload.append('source_text', document.getElementById('sourceText').value);
        payload.append('repo_url', document.getElementById('repoUrl').value);
        if (sourceArchive.files.length > 0) {
            payload.append('source_archive', sourceArchive.files[0]);
        }

        try {
            const response = await fetch('<?= BASE_URL ?>/admin/product/ai_source', {method: 'POST', body: payload});
            const json = await response.json();
            if (json.status !== 'success') {
                alert(i18n.aiError + ': ' + (json.error || 'Unknown error'));
                return;
            }

            const data = json.data || {};
            ['ru', 'en'].forEach((lang) => {
                const block = data.translations?.[lang] || {};
                form.querySelector(`[data-title-input="${lang}"]`).value = block.title || '';
                form.querySelector(`[data-description-input="${lang}"]`).value = block.description || '';
                form.querySelector(`[data-meta-title-input="${lang}"]`).value = block.meta_title || '';
                form.querySelector(`[data-meta-desc-input="${lang}"]`).value = block.meta_desc || '';
                form.querySelector(`[data-meta-keywords-input="${lang}"]`).value = block.meta_keywords || '';
            });

            if (data.suggested_category) {
                const option = Array.from(document.querySelectorAll('#categorySelect option')).find((opt) => opt.textContent.trim().toLowerCase() === String(data.suggested_category).trim().toLowerCase());
                if (option) {
                    document.getElementById('categorySelect').value = option.value;
                }
            }

            analysisResultBox.innerHTML = profileToHtml(data.profile || {}, data.source_kind || 'source');
            profileInsights.textContent = JSON.stringify(data.profile || {}, null, 2);
            document.getElementById('categoryConfidenceLine').textContent = `${i18n.categoryConfidence}: ${data.profile?.category_confidence ?? 0}%`;
            saveDraft();
            updateSummary();
        } catch (e) {
            alert(i18n.networkAnalyze);
        }

        analyzeSourceBtn.disabled = false;
        analyzeSourceBtn.innerHTML = original;
    }

    form.querySelectorAll('[data-action="seo"]').forEach((btn) => btn.addEventListener('click', () => generateSeo(btn.dataset.lang)));
    form.querySelectorAll('[data-action="marketing"]').forEach((btn) => btn.addEventListener('click', () => runMarketing(btn.dataset.lang)));
    form.querySelectorAll('[data-action="code"]').forEach((btn) => btn.addEventListener('click', () => runCodeReview(btn.dataset.lang)));
    analyzeSourceBtn.addEventListener('click', analyzeSource);

    productFileInput.addEventListener('change', updateFilePreview);
    productImagesInput.addEventListener('change', updateImagePreview);
    sourceArchive.addEventListener('change', () => {
        if (sourceArchive.files.length > 0) {
            analysisResultBox.innerHTML = `<div class="text-secondary small">${i18n.sourceArchiveSelected}: ${sourceArchive.files[0].name}</div>`;
        }
    });

    form.querySelectorAll('input, textarea, select').forEach((el) => {
        el.addEventListener('input', () => {
            updateSummary();
            saveDraft();
        });
        el.addEventListener('change', () => {
            updateSummary();
            saveDraft();
        });
    });

    form.querySelectorAll('[type="submit"][data-status]').forEach((btn) => btn.addEventListener('click', () => {
        document.getElementById('productStatusInput').value = btn.dataset.status;
        updateSummary();
    }));

    form.addEventListener('submit', (event) => {
        const ruTitle = form.querySelector('[data-title-input="ru"]').value.trim();
        const enTitle = form.querySelector('[data-title-input="en"]').value.trim();
        const price = parseFloat(document.getElementById('priceInput').value || '0');
        const salePriceRaw = document.getElementById('salePriceInput').value;
        const salePrice = salePriceRaw !== '' ? parseFloat(salePriceRaw) : null;
        const hasFile = isEdit || productFileInput.files.length > 0;

        if (!document.getElementById('categorySelect').value || !ruTitle || !enTitle || !(price > 0) || !hasFile) {
            event.preventDefault();
            alert(i18n.completeBeforePublish);
            return;
        }
        if (salePrice !== null && !(salePrice < price)) {
            event.preventDefault();
            alert(i18n.salePriceLower);
            return;
        }

        if (!isEdit) {
            window.localStorage.removeItem(storageKey);
        }
    });

    restoreDraft();
    updateFilePreview();
    updateSummary();
})();
</script>
