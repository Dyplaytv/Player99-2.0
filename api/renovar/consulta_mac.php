<?php
// REDIRECIONAMENTO PARA O NOVO MERCADO PAGO GOLD
if (isset($_GET['mac'])) {
    header("Location: ../mercado_pago_gold/consulta.php?mac=" . urlencode($_GET['mac']));
    exit;
} else {
    header("Location: ../mercado_pago_gold/consulta.php");
    exit;
}
?>