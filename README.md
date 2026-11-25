
# FormaPay

O **FormaPay** é um sistema de gestão financeira desenvolvido para facilitar a organização do dinheiro da formatura. A plataforma reúne, em um só lugar, as informações financeiras da turma, permitindo acompanhar contribuições, parcelas, arrecadações, despesas e o andamento da meta financeira.

## Sobre o projeto

Organizar uma formatura envolve controlar diferentes pagamentos e gastos, além de manter os participantes informados sobre a situação financeira da turma. O FormaPay foi desenvolvido para tornar esse acompanhamento mais simples, centralizado e transparente.

O sistema possui áreas específicas para a **Comissão** e para os **Participantes**, com funcionalidades adequadas a cada perfil.

## Funcionalidades

### Comissão
- Visualização do resumo financeiro da turma.
- Cadastro e gerenciamento dos participantes.
- Acompanhamento das contribuições e parcelas.
- Registro e organização de receitas e despesas.
- Classificação das movimentações por categorias.
- Consulta de relatórios financeiros.
- Configuração de informações da turma e da meta de arrecadação.

### Participantes
- Visualização do resumo financeiro e do progresso da meta coletiva.
- Consulta das próprias contribuições e parcelas.
- Registro de contribuições e adiantamentos.
- Acompanhamento de vencimentos.
- Atualização de informações do perfil.

## Organização do sistema

O projeto está organizado em módulos para separar as responsabilidades da aplicação:

- **Controller:** recebe e direciona as ações realizadas no sistema.
- **Model:** concentra as operações e regras relacionadas aos dados.
- **View:** reúne as telas apresentadas aos usuários.
- **DB:** contém a conexão e o script de estruturação do banco de dados.
- **uploads:** armazena as imagens de perfil enviadas pelos usuários.

## Tecnologias utilizadas

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- PDO

## Banco de dados

O banco de dados utilizado pelo sistema é o `formapay`. Sua estrutura é criada pelo script `DB/schema.sql`, que contém as tabelas necessárias para gerenciar usuários, turmas, participantes, planos e parcelas de contribuição, categorias e movimentações financeiras.

## Instalação e execução

Para instalar e executar o FormaPay, consulte o arquivo **INSTRUCOES_INSTALACAO.pdf**, disponível nos arquivos deste repositório.

Nele estão descritos os requisitos necessários, a configuração do ambiente, a preparação do banco de dados e o passo a passo para executar o sistema localmente.

## Objetivo

O FormaPay busca facilitar o planejamento financeiro da formatura, ajudando a comissão a administrar os recursos e permitindo que cada participante acompanhe suas contribuições e a evolução da arrecadação da turma.

---

**FormaPay — Organização financeira para tornar a formatura possível.**
