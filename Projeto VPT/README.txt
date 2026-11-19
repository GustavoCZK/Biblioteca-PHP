# 📚 Estante 2D Interativa — Sistema de Biblioteca

Aplicação web desenvolvida em **PHP**, **MySQL**, **HTML5/CSS3** e **JavaScript** que simula uma estante de livros em 2D. O projeto demonstra o uso prático de recursos avançados de banco de dados relacional — como **Views**, **Stored Procedures** e **Triggers** — integrados a uma interface visual interativa.

---

## ✨ Funcionalidades

* **Estante Visual 2D:** Exibição dinâmica dos livros organizados em prateleiras, com cores personalizadas nas lombadas e títulos na vertical.
* **Tooltip Detalhado:** Ao passar o cursor sobre um livro, exibe título, autor, biblioteca, preço unitário, quantidade em estoque e o **valor total calculado (`preço × quantidade`)**.
* **Dois Modos de Consulta ao Banco:**
  * **Modo View (`QLivros`):** Carrega todo o acervo de forma rápida através de uma visão pré-estruturada.
  * **Modo Procedure (`SQBuscarBiblioteca`):** Pesquisa interativa com múltiplos parâmetros (seleção de bibliotecas via *checkbox*, *slider* de preço máximo, busca por título/autor e filtro de disponibilidade em estoque).
* **Edição e Auditoria Automática (`Trigger`):** Ao clicar em qualquer livro, abre-se um modal para atualizar preço e quantidade. Qualquer alteração dispara automaticamente um *Trigger* no MySQL que grava o histórico (valores antigos, novos e data/hora) na tabela de auditoria.

---

## 🗂️ Estrutura de Arquivos

```text
Projeto VPT/
├── src/
│   └── Database.php       # Classe de conexão PDO com o MySQL
├── index.php              # Lógica back-end, consultas SQL e estrutura HTML
├── style.css              # Estilização da estante 2D, modal e tema visual
├── script.js              # Interatividade dos filtros, slider e modal de auditoria
├── banco_completo.sql     # Script SQL para criação do banco, dados, View, Procedure e Trigger
└── README.md              # Documentação do projeto
```

---

## 🗄️ Arquitetura do Banco de Dados (`biblioteca`)

### 1. Tabelas
* **`biblioteca`**: Armazena as unidades (`id`, `nome`, `endereco`).
* **`autor`**: Armazena os autores (`id`, `nome`, `nacionalidade`).
* **`livro`**: Armazena o acervo (`id`, `titulo`, `cor_capa`, `preco`, `quantidade`, `id_autor`, `id_biblioteca`).
* **`auditoria_livro`**: Tabela de log preenchida exclusivamente pelo *Trigger* (`id`, `id_livro`, `preco_antigo`, `preco_novo`, `quantidade_antiga`, `quantidade_nova`, `data_alteracao`).

### 2. Objetos Programáveis no MySQL
* **View `QLivros`**: Realiza o `INNER JOIN` entre `livro`, `autor` e `biblioteca` para listar o catálogo completo sem filtros.
* **Stored Procedure `SQBuscarBiblioteca(p_id_biblioteca, p_preco_max, p_termo, p_apenas_estoque)`**: Filtra os livros dinamicamente com base nos parâmetros enviados pela interface.
* **Trigger `trg_auditoria_livro`**: Disparado `AFTER UPDATE ON livro`. Verifica se houve mudança no preço (`OLD.preco <> NEW.preco`) ou na quantidade (`OLD.quantidade <> NEW.quantidade`) e insere um registro na `auditoria_livro`.

---

## 🚀 Como Executar o Projeto (Laragon / XAMPP)

1. **Clonar ou copiar os arquivos:**
   Coloque todos os arquivos na pasta pública do seu servidor local (ex.: `C:\laragon\www\Projeto VPT`).

2. **Configurar o Banco de Dados:**
   * Abra o gerenciador MySQL (HeidiSQL, phpMyAdmin ou Workbench).
   * Execute o script SQL completo do projeto para criar o banco `biblioteca`, as tabelas, os dados de exemplo, a *View*, a *Procedure* e o *Trigger*.

3. **Verificar a Conexão (`src/Database.php`):**
   Confira se o usuário e senha do MySQL correspondem ao seu ambiente local (no Laragon padrão, o usuário é `root` e a senha costuma ser `''` ou `'root'`).

4. **Acessar no Navegador:**
   Inicie o Apache e o MySQL no Laragon e acesse:
   ```text
   http://localhost/Projeto%20VPT/
   ```

---

## 🛠️ Tecnologias Utilizadas

* **Back-end:** PHP 8+ (PDO)
* **Banco de Dados:** MySQL 8+ / MariaDB
* **Front-end:** HTML5, CSS3 (Flexbox & Gradientes) e JavaScript (Vanilla)
