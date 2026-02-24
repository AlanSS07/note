<?php include 'session.php'; // Assume-se que aqui já tem session_start() ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Pagemento</title>
    <style> #produto, #marca, #modelo { text-transform: uppercase; } </style>
</head>
<body>
    <h2>Adicionar Pagamento</h2>
    <form method="POST" action="salvar_compra.php">
        <label>Produto / Serviço:</label><br>
        <input type="text" id="produto" name="produto" required><br><br>

        <label>Marca / Empresa:</label><br>
        <input type="text" id="marca" name="marca"><br><br>

        <label>Modelo / Descrição:</label><br>
        <input type="text" id="modelo" name="modelo"><br><br>

        <label>Valor da Parcela (R$):</label><br>
        <input type="number" name="valor" step="0.01" value="0.00"><br><br>

        <label>Quantidade:</label><br>
        <input type="number" name="quantidade" value="1" min="1"><br><br>

        <label>Número de Parcelas:</label><br>
        <input type="number" name="parcelas_total" value="1" min="1"><br><br>

        <label>Data do 1º Vencimento:</label><br>
        <input type="date" id="data_vencimento" name="data_vencimento" value="<?php echo date('Y-m-d'); ?>" required><br><br>

        <div id="parcelas_container">
            <!-- Campos de pagamento por parcela serão gerados aqui -->
        </div>

        <small>Se informar mais de 1 parcela, pode marcar a data de pagamento de cada parcela abaixo.</small><br><br>

        <script>
        (function(){
            function addParcelFields(n) {
                const container = document.getElementById('parcelas_container');
                container.innerHTML = '';
                const dataV = document.getElementById('data_vencimento').value || new Date().toISOString().slice(0,10);
                const parts = dataV.split('-');
                const baseYear = parseInt(parts[0], 10);
                const baseMonth = parseInt(parts[1], 10) - 1; // mês indexado em 0
                const baseDay = parseInt(parts[2], 10);
                for (let i=1;i<=n;i++){
                    // Cria a data usando (ano, mêsIndex, dia) para evitar comportamento UTC ao usar string ISO
                    const venc = new Date(baseYear, baseMonth + (i-1), baseDay);
                    const yyyy = venc.getFullYear();
                    const mm = String(venc.getMonth()+1).padStart(2,'0');
                    const dd = String(venc.getDate()).padStart(2,'0');
                    const vencStr = yyyy+'-'+mm+'-'+dd;

                    const div = document.createElement('div');
                    div.innerHTML = '<label>Parcela '+i+' - Vencimento: '+vencStr+'</label><br>' +
                                    '<label>Data de Pagamento (Opcional):</label><br>' +
                                    '<input type="date" name="data_conclusao[]"><br><br>';
                    container.appendChild(div);
                }
            }
            const parcelasInput = document.querySelector('input[name="parcelas_total"]');
            parcelasInput.addEventListener('change', function(){ addParcelFields(Math.max(1, parseInt(this.value)||1)); });
            document.getElementById('data_vencimento').addEventListener('change', function(){
                addParcelFields(Math.max(1, parseInt(parcelasInput.value)||1));
            });
            // Inicializa com valor atual
            addParcelFields(Math.max(1, parseInt(parcelasInput.value)||1));
        })();
        </script>

        <input type="submit" value="Salvar Pagamento">
    </form>
    <p><a href="listar_compras.php">Voltar</a> | <a href="index.php">Início</a></p>
</body>
</html>