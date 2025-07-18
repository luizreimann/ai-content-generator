# AI Content Generator

**AI Content Generator** é um plugin WordPress que permite gerar posts completos usando a API da OpenAI diretamente do painel de administração. Com ele, você insere um prompt, escolhe modelos de texto e imagem, define tamanho mínimo em palavras, quantidade de imagens e a chave da API, e o plugin cria o post com título, descrição, conteúdo HTML e imagens.

## Funcionalidades

- **Geração de artigo**: envie um prompt em português, especifique o modelo de linguagem e mínimo de palavras.
- **Geração de imagens**: escolha o modelo de imagem e número de imagens a serem geradas pela OpenAI (ex.: DALL·E 3, GPT-Image-1).
- **Preview interativo**: visualize título, meta description, tags, conteúdo e imagens antes de salvar.
- **Regeneração de imagem**: passe o mouse sobre cada imagem e clique em “Regenerar” para substituir individualmente.
- **Salvamento automático**: salve o post gerado no WordPress com todas as imagens sideloaded e definidas como featured image.
- **Configuração de API Key**: armazene sua chave da OpenAI diretamente no plugin, sem precisar editar código.
- **REST API**: endpoints próprios para geração (`/aicg/v1/generate`), salvamento (`/aicg/v1/save`), regeneração de imagens (`/aicg/v1/regenerate-image`) e configuração de chave (`/aicg/v1/save-api-key`).

## Requisitos

- WordPress 6.0 ou superior  
- PHP 7.4 ou superior  
- Extensão cURL habilitada  
- Chave de API da OpenAI válida

## Instalação

1. Faça o upload da pasta `ai-content-generator` para o diretório `wp-content/plugins/`.  
2. Ative o plugin no painel **Plugins** do WordPress.  
3. Acesse **Conteúdo IA > Gerador de Conteúdo** no menu administrativo.

## Uso

1. **Prompt**: digite o texto inicial que descreve o assunto do post.  
2. **Modelo de Texto**: selecione o modelo (ex.: `gpt-4o`, `gpt-4o-mini`).  
3. **Modelo de Imagem**: selecione o modelo de geração de imagem (ex.: `dall-e-3`, `gpt-image-1`).  
4. **Quantidade de Imagens**: defina quantas imagens serão criadas.  
5. **Tamanho Mínimo (palavras)**: informe o número mínimo de palavras para o artigo.  
6. **API Key**: cole sua chave da OpenAI no campo correspondente.  
7. Clique em **Gerar Artigo** e aguarde o preview ser exibido.  
8. Para cada imagem, passe o mouse e clique em **Regenerar** para trocar individualmente.  
9. Quando estiver satisfeito, clique em **Salvar Artigo** para publicar no WordPress.

## REST API

O plugin registra as seguintes rotas REST:

| Rota                                   | Método | Descrição                                   |
| -------------------------------------- | ------ | ------------------------------------------- |
| `/wp-json/aicg/v1/generate`            | POST   | Gera título, excerpt, tags, conteúdo e imagens. Parâmetros: `prompt`, `text_model`, `image_model`, `num_images`, `min_words`, `api_key`. |
| `/wp-json/aicg/v1/save`                | POST   | Salva o post gerado no WP. Recebe todo o JSON de preview. |
| `/wp-json/aicg/v1/regenerate-image`    | POST   | Regenera uma única imagem. Parâmetros: `prompt`, `api_key`. Retorna `url`. |
| `/wp-json/aicg/v1/save-api-key`        | POST   | Salva a chave da API no banco. Parâmetros: `api_key`.      |

Todas as rotas exigem autenticação com nonce (`X-WP-Nonce`) e capacidade de editar posts (ou `manage_options` para salvar a API key).

## Personalização

- **Modelos**: altere as constantes de modelo em `class-aicg-openai.php` ou use o seletor na interface.  
- **Prompt Base**: modifique o prompt de sistema em `generate_content()` para ajustar tom ou estilo.  
- **Estilos**: adicione regras CSS em `assets/css/aicg-admin.css` para personalizar o visual.

## Suporte e Contribuição

- Abra issues em: https://github.com/luizreimann/ai-content-generator  
- Envie pull requests com melhorias ou correções.  
- Para dúvidas rápidas, contate o autor.

---

**Autor:** Luiz Reimann  
**License:** GPLv3 or later