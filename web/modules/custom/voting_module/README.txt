# Sistema de Votação do Drupal

## Visão Geral

Este é um sistema de votação desenvolvido como um módulo customizado para Drupal 10. 
Ele permite que administradores registrem perguntas com várias opções de resposta, e os usuários possam votar nessas perguntas. 
O sistema também fornece uma API para interação com aplicativos de terceiros autorizados.

## Estrutura do Módulo

### Diretório `src`

- **Controller**
  - `ApiController.php`: Gerencia os endpoints da API para interação com o sistema de votação.

- **Entity**
  - `Question.php`: Entidade para perguntas.
  - `AnswerOption.php`: Entidade para opções de resposta.
  - `ApiKey.php`: Entidade para gerenciar chaves de API.
  - `Assessment.php`: Entidade para exibir o total de votos para cada questao e cada opcao de resposta.
  - `Result.php`: Entidade para registrar e exibir uma lista com todos os votos.

- **Event**
  - `VoteEvent.php`: Evento disparado quando um voto é realizado.
  - `VoteResultEvent.php`: Evento disparado ao calcular os resultados da votação.

- **EventSubscriber**
  - `VotingEventSubscriber.php`: Inscrito para eventos de votação.

- **Form**
  - `QuestionForm.php`: Formulário para adicionar/editar perguntas.
  - `QuestionDeleteForm.php`: Formulário para excluir perguntas.
  - `AnswerOptionForm.php`: Formulário para adicionar/editar opções de resposta.
  - `AnswerOptionDeleteForm.php`: Formulário para excluir opções de resposta.
  - `VotingSettingsForm.php`: Formulário para configurações do módulo.
  - `ApiKeyForm.php`: Formulário para gerenciar chaves de API.

- **Plugin**
  - **Block**
    - `VotingBlock.php`: Bloco para realizar votações.
    - `ResultBlock.php`: Bloco para exibir resultados de votações.
  - **EntityReferenceSelection**
    - `QuestionReferenceSelection.php`: Fornece controle de acesso específico para o tipo de entidade Question.

- **Service**
  - `VotingService.php`: Serviço para lógica de votação.
  - `VotingResultsService.php`: Serviço para processar resultados de votação.

- **Access**
  - `QuestionAccessControlHandler.php`: Handler de controle de acesso para perguntas.
  - `AnswerOptionAccessControlHandler.php`: Handler de controle de acesso para opções de resposta.

- **tests/src**
  - **Functional**
    - `VotingFunctionalTest.php`: Testes funcionais para garantir o funcionamento correto do módulo.
  - **Unit**
    - `VotingServiceTest.php`: Testes unitários para `VotingService`.

- **templates**
  - `voting-block.html.twig`: Template para o bloco de votação.
  - `voting-results.html.twig`: Template para visualizar resultados.

## Requisitos Funcionais

1. **Registro de Perguntas**
   - Administradores podem registrar perguntas com títulos e identificadores únicos.
   - Cada pergunta pode ter múltiplas opções de resposta.

2. **Votação**
   - Usuários podem votar em perguntas registradas, selecionando uma das opções de resposta.
   - Apenas usuários logados com as permissões corretas podem votar.

3. **Resultados**
   - Administradores podem consultar o número de votos recebidos por cada pergunta e a porcentagem de votos.

4. **Exibição de Votos**
   - Integração com Drupal para exibir votos e configurar a visibilidade do total de votos após o usuário votar.

5. **API**
   - O sistema fornece uma API para interação com aplicativos de terceiros autorizados, permitindo que votos registrados estejam disponíveis em um aplicativo nativo.

6. **Segurança**
   - O sistema deve ser seguro, protegendo os dados de votação e evitando manipulações indevidas.
   - Autenticação via chaves de API para interações com a API.

## Requisitos Não Funcionais

- **Facilidade de Uso**: Interface amigável e intuitiva para administradores e usuários.
- **Desempenho**: Respostas rápidas e eficientes às ações dos usuários.
- **Manutenção**: Código organizado e bem documentado para facilitar a manutenção e extensões futuras.

## Configuração e Uso

### Instalação

1. **Clonar o Repositório**: Clone o repositório do módulo para o diretório `modules/custom` da sua instalação do Drupal.
2. **Habilitar o Módulo**: Habilite o módulo através da interface de administração do Drupal ou usando Drush:
   ```sh
   drush en voting_module

## Configuração

### Configurações do Módulo
Acesse **Configuration -> System -> Voting Module Settings** para configurar as opções do módulo, como habilitar/desabilitar votação e exibição de resultados.

### Gerenciamento de Chaves de API
Acesse **Configuration -> Web services -> API Keys** para gerenciar chaves de API que serão usadas por aplicativos de terceiros para interagir com o sistema de votação.
**(Importante adicionar chave da API no arquivo** `js/voting_block.js na linha 35` **para ser possível enviar votos no sistema.)**

### Gerenciar funcionalidades
 - **Manage Questions**: `/admin/content/question`
 - **Manage Answer Options**: `/admin/content/answer_option`
 - **Manage Assessments**: `/admin/content/assessment`
 - **Manage Results**: `/admin/content/result`
 - **Adicionar bloco** `Voting Block` **para visualizar questões e votar.**
 - **Adicionar bloco** `Result Block` **para exibir o resultado da votação para questões específicas.**
 
#### OBS:
 - **Importante adicionar chave da API no arquivo** `js/voting_block.js na linha 35` **para ser possível enviar votos no sistema.**
 - O usuário adiministrador está habilitado para votar quantas vezes quiser.
 - Outros usuários tem o comportamento de votação padrão.

## Uso da API

Para interagir com a API, os aplicativos de terceiros devem autenticar-se usando chaves de API. As chaves de API podem ser geradas e gerenciadas na interface de administração do Drupal em **Configuração -> Sistema de Votação -> Chaves de API**.

### Como Utilizar a API

#### Obter todas as perguntas
- **Endpoint**: `/api/voting/questions`
- **Método**: GET
- **Descrição**: Retorna uma lista de todas as perguntas disponíveis.
- **Exemplo de Requisição no Postman**:
  - Método: GET
  - URL: `http://seu-dominio.com/api/voting/questions`
  - Headers:
    - `Authorization`: `Bearer {API_KEY}`

#### Obter detalhes de uma pergunta
- **Endpoint**: `/api/voting/questions/{question_id}`
- **Método**: GET
- **Descrição**: Retorna os detalhes de uma pergunta específica pelo ID.
- **Exemplo de Requisição no Postman**:
  - Método: GET
  - URL: `http://seu-dominio.com/api/voting/questions/{question_id}`
  - Headers:
    - `Authorization`: `Bearer {API_KEY}`

#### Submeter um voto
- **Endpoint**: `/api/voting/vote`
- **Método**: POST
- **Descrição**: Submete um voto para uma pergunta específica.
- **Exemplo de Corpo da Requisição**:
  ```json
  {
    "question_id": "1",
    "answer_id": "2",
    "selected_option": "Sim"
  }