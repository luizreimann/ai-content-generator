<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap">
    <div class="row">

        <div class="col-md-6">
            <section class="card mb-4">
                    <h1 class="h5"><?php esc_html_e( 'Gerar Post com IA', 'ai-content-generator' ); ?></h1>
                    <textarea
                        id="aicg-prompt"
                        class="form-control mb-3"
                        rows="6"
                        placeholder="<?php esc_attr_e( 'Digite o prompt para gerar o artigo...', 'ai-content-generator' ); ?>"></textarea>
                    <button id="aicg-generate-button" class="btn btn-primary w-100 mb-2">
                        <?php esc_html_e( 'Gerar Artigo', 'ai-content-generator' ); ?>
                    </button>
                    <button id="aicg-save-button" class="btn btn-success w-100" disabled>
                        <?php esc_html_e( 'Salvar Artigo', 'ai-content-generator' ); ?>
                    </button>
            </section>

            <section class="card">
                <h2 class="h5 mt-2"><?php esc_attr_e( 'Opções', 'ai-content-generator' ); ?></h2>
                <p>
                    <span class="fs-6 fw-normal ml-5"><?php esc_attr_e('Consulte os custos de cada modelo visitando a ', 'ai-content-generator')?></span>
                    <a href="https://platform.openai.com/docs/pricing" target="_blank" rel="nofollow" class="fs-6 fw-normal"><?php esc_attr_e('documentação', 'ai-content-generator')?></a>
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group mb-3">
                            <label class="input-group-text" for="inputModeloTexto"><?php esc_attr_e( 'Modelo de Texto', 'ai-content-generator' ); ?></label>
                            <select class="form-select" id="inputModeloTexto">
                                <option value="gpt-4o" selected>gpt-4o</option>
                                <option value="gpt-4o-mini">GPT-4o mini</option>
                                <option value="gpt-4.1-mini">gpt-4.1 mini</option>
                                <option value="gpt-4.1-nano">gpt-4.1 nano</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group mb-3">
                            <label class="input-group-text" for="inputModeloImagem"><?php esc_attr_e( 'Modelo de Imagem', 'ai-content-generator' ); ?></label>
                            <select class="form-select" id="inputModeloImagem">
                                <option value="dall-e-3" selected>dall-E 3</option>
                                <option value="dall-e-2">dall-E 2</option>
                                <option value="gpt-image-1">gpt-image-1</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group mb-3">
                            <label class="input-group-text" for="inputNumeroPalavras">
                                <?php esc_attr_e( 'Tamanho min.', 'ai-content-generator' ); ?>
                            </label>
                            <input
                                type="number"
                                id="inputNumeroPalavras"
                                class="form-control"
                                min="1500"
                                placeholder="<?php esc_attr_e( 'Em palavras', 'ai-content-generator' ); ?>"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group mb-3">
                            <label class="input-group-text" for="inputNumeroImagens">
                                <?php esc_attr_e( 'Quantidade de Imagens', 'ai-content-generator' ); ?>
                            </label>
                            <input
                                type="number"
                                id="inputNumeroImagens"
                                class="form-control"
                                min="1"
                                value="2"
                            />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group mb-3">
                        <label class="input-group-text" for="inputApiKey">
                            <?php esc_attr_e( 'API Key', 'ai-content-generator' ); ?>
                        </label>
                        <input
                            type="password"
                            id="inputApiKey"
                            class="form-control"
                            placeholder="<?php esc_attr_e( 'Insira sua chave da OpenAI', 'ai-content-generator' ); ?>"
                            value="<?php echo esc_attr( get_option( 'aicg_openai_api_key', '' ) ); ?>"
                        />
                    </div>
                </div>
            </section>
        </div>

        <div class="col-md-6">
            <section class="card">
                <div class="card-body">
                    <h2 class="h5"><?php esc_html_e( 'Preview do Artigo', 'ai-content-generator' ); ?></h2>
                    <div id="aicg-preview"></div>
                </div>
            </section>
        </div>

    </div>
</div>