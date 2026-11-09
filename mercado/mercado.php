
<?php
session_start();

/* CONEXÃO COM O BANCO DE DADOS */
$host = "localhost";
$banco = "supermercado";
$usuario = "root";
$senha = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

/* FUNÇÕES */
function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function voltar($pagina = "inicio") {
    header("Location: mercado.php?pagina=" . $pagina);
    exit;
}

function mensagem($tipo, $texto) {
    $_SESSION["mensagem"] = [
        "tipo" => $tipo,
        "texto" => $texto
    ];
}

/* PROCESSAMENTO DOS FORMULÁRIOS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $acao = $_POST["acao"] ?? "";

    try {

        /* CADASTRAR OU EDITAR CATEGORIA */
        if ($acao === "salvar_categoria") {

            $id = (int)($_POST["id"] ?? 0);
            $nome = trim($_POST["nome"] ?? "");
            $descricao = trim($_POST["descricao"] ?? "");

            if ($nome === "") {
                throw new Exception("Informe o nome da categoria.");
            }

            $sql = "SELECT id FROM categorias
                    WHERE nome = ? AND id <> ?";

            $consulta = $pdo->prepare($sql);
            $consulta->execute([$nome, $id]);

            if ($consulta->fetch()) {
                throw new Exception("Essa categoria já existe.");
            }

            if ($id > 0) {
                $sql = "UPDATE categorias
                        SET nome = ?, descricao = ?
                        WHERE id = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $descricao ?: null, $id]);

                mensagem("sucesso", "Categoria atualizada!");
            } else {
                $sql = "INSERT INTO categorias (nome, descricao)
                        VALUES (?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $descricao ?: null]);

                mensagem("sucesso", "Categoria cadastrada!");
            }

            voltar("categorias");
        }

        /* EXCLUIR CATEGORIA */
        if ($acao === "excluir_categoria") {

            $id = (int)($_POST["id"] ?? 0);

            $stmt = $pdo->prepare(
                "DELETE FROM categorias WHERE id = ?"
            );

            $stmt->execute([$id]);

            mensagem("sucesso", "Categoria excluída!");
            voltar("categorias");
        }

        /* CADASTRAR OU EDITAR PRODUTO */
        if ($acao === "salvar_produto") {

            $id = (int)($_POST["id"] ?? 0);
            $codigo = trim($_POST["codigo"] ?? "");
            $nome = trim($_POST["nome"] ?? "");
            $descricao = trim($_POST["descricao"] ?? "");
            $categoria = (int)($_POST["categoria_id"] ?? 0);
            $preco = str_replace(",", ".", trim($_POST["preco"] ?? ""));
            $estoque = $_POST["estoque"] ?? "";
            $estoqueMinimo = $_POST["estoque_minimo"] ?? "";
            $validade = trim($_POST["validade"] ?? "");

            if ($codigo === "" || $nome === "" || $categoria <= 0) {
                throw new Exception("Preencha os campos obrigatórios.");
            }

            if (!is_numeric($preco) || (float)$preco <= 0) {
                throw new Exception("Informe um preço válido maior que zero.");
            }

            if (
                filter_var($estoque, FILTER_VALIDATE_INT) === false ||
                filter_var($estoqueMinimo, FILTER_VALIDATE_INT) === false ||
                (int)$estoque < 0 ||
                (int)$estoqueMinimo < 0
            ) {
                throw new Exception("Estoque e estoque mínimo devem ser números inteiros iguais ou maiores que zero.");
            }

            $stmt = $pdo->prepare(
                "SELECT id FROM categorias WHERE id = ?"
            );
            $stmt->execute([$categoria]);

            if (!$stmt->fetch()) {
                throw new Exception("Selecione uma categoria existente.");
            }

            $stmt = $pdo->prepare(
                "SELECT id FROM produtos WHERE codigo = ? AND id <> ?"
            );
            $stmt->execute([$codigo, $id]);

            if ($stmt->fetch()) {
                throw new Exception("Esse código já está cadastrado.");
            }

            if ($validade !== "") {
                $data = DateTime::createFromFormat("!Y-m-d", $validade);

                if (!$data || $data->format("Y-m-d") !== $validade) {
                    throw new Exception("Informe uma data de validade válida.");
                }

                $validadeBanco = $validade;
            } else {
                $validadeBanco = null;
            }

            if ($id > 0) {

                $sql = "UPDATE produtos SET
                        codigo = ?,
                        nome = ?,
                        descricao = ?,
                        categoria_id = ?,
                        preco = ?,
                        estoque = ?,
                        estoque_minimo = ?,
                        validade = ?
                        WHERE id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $codigo,
                    $nome,
                    $descricao ?: null,
                    $categoria,
                    $preco,
                    $estoque,
                    $estoqueMinimo,
                    $validadeBanco,
                    $id
                ]);

                mensagem("sucesso", "Produto atualizado!");

            } else {

                $sql = "INSERT INTO produtos
                        (codigo, nome, descricao, categoria_id,
                         preco, estoque, estoque_minimo, validade)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $codigo,
                    $nome,
                    $descricao ?: null,
                    $categoria,
                    $preco,
                    $estoque,
                    $estoqueMinimo,
                    $validadeBanco
                ]);

                mensagem("sucesso", "Produto cadastrado!");
            }

            voltar("produtos");
        }

        /* EXCLUIR PRODUTO */
        if ($acao === "excluir_produto") {

            $id = (int)($_POST["id"] ?? 0);

            $stmt = $pdo->prepare(
                "DELETE FROM produtos WHERE id = ?"
            );

            $stmt->execute([$id]);

            mensagem("sucesso", "Produto excluído!");
            voltar("produtos");
        }

    } catch (PDOException $e) {

        mensagem("erro", "Não foi possível realizar a operação. Verifique os dados.");

        voltar("inicio");

    } catch (Exception $e) {

        mensagem("erro", $e->getMessage());

        voltar("inicio");
    }
}

/* PÁGINA ATUAL */
$pagina = $_GET["pagina"] ?? "inicio";

/* LISTAR CATEGORIAS */
$categorias = $pdo->query(
    "SELECT * FROM categorias ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);

/* MENSAGENS */
$aviso = $_SESSION["mensagem"] ?? null;
unset($_SESSION["mensagem"]);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistema de Supermercado</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <h1>Sistema de Supermercado</h1>
    <p>Gerenciamento de produtos e categorias</p>
</header>

<nav>
    <a href="mercado.php?pagina=inicio">Início</a>
    <a href="mercado.php?pagina=produtos">Produtos</a>
    <a href="mercado.php?pagina=categorias">Categorias</a>
</nav>

<main>

<?php if ($aviso): ?>
    <div class="<?= h($aviso["tipo"]) ?>">
        <?= h($aviso["texto"]) ?>
    </div>
<?php endif; ?>


<?php
/* PÁGINA INICIAL */
if ($pagina === "inicio"):

    $totalProdutos = $pdo->query(
        "SELECT COUNT(*) FROM produtos"
    )->fetchColumn();

    $totalCategorias = $pdo->query(
        "SELECT COUNT(*) FROM categorias"
    )->fetchColumn();

    $estoqueBaixo = $pdo->query(
        "SELECT COUNT(*) FROM produtos
         WHERE estoque <= estoque_minimo"
    )->fetchColumn();
?>

    <h2>Painel inicial</h2>

    <div class="card">
        Produtos cadastrados
        <strong><?= $totalProdutos ?></strong>
    </div>

    <div class="card">
        Categorias cadastradas
        <strong><?= $totalCategorias ?></strong>
    </div>

    <div class="card">
        Estoque baixo
        <strong><?= $estoqueBaixo ?></strong>
    </div>

    <h2>Bem-vindo ao sistema!</h2>

    <p>Utilize o menu para gerenciar os produtos e categorias.</p>


<?php
/* LISTAGEM DE PRODUTOS */
elseif ($pagina === "produtos"):

    $busca = trim($_GET["busca"] ?? "");
    $filtroCategoria = (int)($_GET["categoria"] ?? 0);

    $sql = "SELECT p.*, c.nome AS categoria_nome
            FROM produtos p
            INNER JOIN categorias c
            ON p.categoria_id = c.id
            WHERE 1=1";

    $parametros = [];

    if ($busca !== "") {
        $sql .= " AND (p.nome LIKE ? OR p.codigo LIKE ?)";
        $parametros[] = "%$busca%";
        $parametros[] = "%$busca%";
    }

    if ($filtroCategoria > 0) {
        $sql .= " AND p.categoria_id = ?";
        $parametros[] = $filtroCategoria;
    }

    $sql .= " ORDER BY p.nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <h2>Produtos cadastrados</h2>

    <a class="botao" href="mercado.php?pagina=form_produto">
        Novo produto
    </a>

    <form method="GET">
        <input type="hidden" name="pagina" value="produtos">

        <label>Pesquisar produto:</label>
        <input type="text" name="busca"
               placeholder="Nome ou código"
               value="<?= h($busca) ?>">

        <label>Filtrar por categoria:</label>

        <select name="categoria">
            <option value="0">Todas as categorias</option>

            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat["id"] ?>"
                    <?= $filtroCategoria == $cat["id"] ? "selected" : "" ?>>
                    <?= h($cat["nome"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Pesquisar</button>

        <a class="botao" href="mercado.php?pagina=produtos">
            Limpar filtros
        </a>
    </form>

    <?php if (count($produtos) > 0): ?>

    <table>
        <tr>
            <th>Código</th>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Preço</th>
            <th>Estoque</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($produtos as $p): ?>

        <tr>
            <td><?= h($p["codigo"]) ?></td>
            <td><?= h($p["nome"]) ?></td>
            <td><?= h($p["categoria_nome"]) ?></td>
            <td>R$ <?= number_format($p["preco"], 2, ",", ".") ?></td>

            <td>
                <?= $p["estoque"] ?>

                <?php if ($p["estoque"] <= $p["estoque_minimo"]): ?>
                    <span style="color:red;">(Estoque baixo)</span>
                <?php endif; ?>
            </td>

            <td class="acoes">
                <a class="botao"
                   href="mercado.php?pagina=detalhe_produto&id=<?= $p["id"] ?>">
                    Ver
                </a>

                <a class="botao"
                   href="mercado.php?pagina=form_produto&id=<?= $p["id"] ?>">
                    Editar
                </a>

                <form method="POST"
                      onsubmit="return confirm('Deseja excluir este produto?');">

                    <input type="hidden" name="acao" value="excluir_produto">
                    <input type="hidden" name="id" value="<?= $p["id"] ?>">

                    <button class="excluir" type="submit">
                        Excluir
                    </button>
                </form>
            </td>
        </tr>

        <?php endforeach; ?>

    </table>

    <?php else: ?>

        <p>Nenhum produto encontrado.</p>

    <?php endif; ?>


<?php
/* FORMULÁRIO DE PRODUTO */
elseif ($pagina === "form_produto"):

    $id = (int)($_GET["id"] ?? 0);

    $produto = [
        "codigo" => "",
        "nome" => "",
        "descricao" => "",
        "categoria_id" => "",
        "preco" => "",
        "estoque" => 0,
        "estoque_minimo" => 0,
        "validade" => ""
    ];

    if ($id > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM produtos WHERE id = ?"
        );

        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            echo "<p>Produto não encontrado.</p>";
        } else {
            $produto = $resultado;
        }
    }
?>

    <h2><?= $id > 0 ? "Editar produto" : "Cadastrar produto" ?></h2>

    <form method="POST">

        <input type="hidden" name="acao" value="salvar_produto">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Código:</label>
        <input type="text" name="codigo" required
               value="<?= h($produto["codigo"]) ?>">

        <label>Nome:</label>
        <input type="text" name="nome" required
               value="<?= h($produto["nome"]) ?>">

        <label>Descrição:</label>
        <textarea name="descricao"><?= h($produto["descricao"]) ?></textarea>

        <label>Categoria:</label>

        <select name="categoria_id" required>
            <option value="">Selecione uma categoria</option>

            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat["id"] ?>"
                    <?= $produto["categoria_id"] == $cat["id"] ? "selected" : "" ?>>
                    <?= h($cat["nome"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Preço:</label>
        <input type="number" name="preco" step="0.01"
               min="0.01" required
               value="<?= h($produto["preco"]) ?>">

        <label>Estoque:</label>
        <input type="number" name="estoque" min="0" required
               value="<?= h($produto["estoque"]) ?>">

        <label>Estoque mínimo:</label>
        <input type="number" name="estoque_minimo" min="0" required
               value="<?= h($produto["estoque_minimo"]) ?>">

        <label>Validade:</label>
        <input type="date" name="validade"
               value="<?= h($produto["validade"]) ?>">

        <button type="submit">Salvar produto</button>

        <a class="botao" href="mercado.php?pagina=produtos">
            Cancelar
        </a>

    </form>


<?php
/* DETALHES DO PRODUTO */
elseif ($pagina === "detalhe_produto"):

    $id = (int)($_GET["id"] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT p.*, c.nome AS categoria_nome
         FROM produtos p
         INNER JOIN categorias c ON p.categoria_id = c.id
         WHERE p.id = ?"
    );

    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p):
?>

    <h2>Produto não encontrado.</h2>

<?php else: ?>

    <h2>Detalhes do produto</h2>

    <p><strong>Código:</strong> <?= h($p["codigo"]) ?></p>
    <p><strong>Nome:</strong> <?= h($p["nome"]) ?></p>
    <p><strong>Descrição:</strong> <?= h($p["descricao"]) ?></p>
    <p><strong>Categoria:</strong> <?= h($p["categoria_nome"]) ?></p>
    <p><strong>Preço:</strong> R$ <?= number_format($p["preco"], 2, ",", ".") ?></p>
    <p><strong>Estoque:</strong> <?= h($p["estoque"]) ?></p>
    <p><strong>Estoque mínimo:</strong> <?= h($p["estoque_minimo"]) ?></p>
    <p><strong>Validade:</strong> <?= $p["validade"] ? h($p["validade"]) : "Não informada" ?></p>

    <?php if ($p["validade"] && $p["validade"] < date("Y-m-d")): ?>
        <div class="aviso">Atenção: produto com validade vencida!</div>
    <?php endif; ?>

    <?php if ($p["estoque"] <= $p["estoque_minimo"]): ?>
        <div class="aviso">Atenção: estoque baixo!</div>
    <?php endif; ?>

    <a class="botao"
       href="mercado.php?pagina=form_produto&id=<?= $p["id"] ?>">
        Editar produto
    </a>

    <a class="botao" href="mercado.php?pagina=produtos">
        Voltar
    </a>

<?php endif; ?>


<?php
/* LISTAGEM DE CATEGORIAS */
elseif ($pagina === "categorias"):
?>

    <h2>Categorias cadastradas</h2>

    <a class="botao" href="mercado.php?pagina=form_categoria">
        Nova categoria
    </a>

    <?php if (count($categorias) > 0): ?>

    <table>
        <tr>
            <th>Nome</th>
            <th>Descrição</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($categorias as $cat): ?>

        <tr>
            <td><?= h($cat["nome"]) ?></td>
            <td><?= h($cat["descricao"]) ?></td>

            <td class="acoes">

                <a class="botao"
                   href="mercado.php?pagina=form_categoria&id=<?= $cat["id"] ?>">
                    Editar
                </a>

                <form method="POST"
                      onsubmit="return confirm('Deseja excluir esta categoria?');">

                    <input type="hidden" name="acao" value="excluir_categoria">
                    <input type="hidden" name="id" value="<?= $cat["id"] ?>">

                    <button class="excluir" type="submit">
                        Excluir
                    </button>
                </form>

            </td>
        </tr>

        <?php endforeach; ?>

    </table>

    <?php else: ?>

        <p>Nenhuma categoria cadastrada.</p>

    <?php endif; ?>


<?php
/* FORMULÁRIO DE CATEGORIA */
elseif ($pagina === "form_categoria"):

    $id = (int)($_GET["id"] ?? 0);

    $categoria = [
        "nome" => "",
        "descricao" => ""
    ];

    if ($id > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM categorias WHERE id = ?"
        );

        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $categoria = $resultado;
        } else {
            echo "<p>Categoria não encontrada.</p>";
        }
    }
?>

    <h2><?= $id > 0 ? "Editar categoria" : "Cadastrar categoria" ?></h2>

    <form method="POST">

        <input type="hidden" name="acao" value="salvar_categoria">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Nome da categoria:</label>
        <input type="text" name="nome" maxlength="80"
               required value="<?= h($categoria["nome"]) ?>">

        <label>Descrição:</label>
        <textarea name="descricao"><?= h($categoria["descricao"]) ?></textarea>

        <button type="submit">Salvar categoria</button>

        <a class="botao" href="mercado.php?pagina=categorias">
            Cancelar
        </a>

    </form>


<?php else: ?>

    <h2>Página não encontrada.</h2>
    <a class="botao" href="mercado.php?pagina=inicio">Voltar ao início</a>

<?php endif; ?>

</main>

<footer>
    <p>Sistema de Supermercado - 2026</p>
</footer>

</body>
</html>
