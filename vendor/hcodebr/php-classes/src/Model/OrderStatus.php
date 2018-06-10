<?php 

namespace Hcode\Model;//serve para dizer onde esta localizada

//classes
use \Hcode\DB\Sql;
use \Hcode\Model;

//status dos pedidos
class OrderStatus extends Model {
	const EM_ABERTO = 1;
	const AGUARDANDO_PAGAMENTO = 2;
	const PAGO = 3;
	const ENTREGUE = 4;

	//tras todos os status do banco de dados
	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_ordersstatus ORDER BY desstatus");
	}
}
?>