<?php 
namespace Hcode\Model;//serve pra dizer onde esta localizado

//classes
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;


class Order extends Model {
	const SUCCESS = "Order-Success";//constante para a mensagem de erro
	const ERROR = "Order-Error";//constante para a mensagem de sucesso

	//metodo para salvar os pedidos
	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
			':idorder'=>$this->getidorder(),
			':idcart'=>$this->getidcart(),
			':iduser'=>$this->getiduser(),
			':idstatus'=>$this->getidstatus(),
			':idaddress'=>$this->getidaddress(),
			':vltotal'=>$this->getvltotal()
		]);
		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	//seleciona a tabelo no banco de dados dos pedidos
	public function get($idorder)
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder
		", [
			':idorder'=>$idorder
		]);
		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	//lista todos os pedidos
	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
		");
	}

	//apaga o pedido
	public function delete()
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
			':idorder'=>$this->getidorder()
		]);
	}

	//PEGA O CARRINHO DE UM PEDIDO ESPECIFICO
	public function getCart()
	{
		$cart = new Cart();
		$cart->get((int)$this->getidcart());
		return $cart;
	}

	//inicia a sessão do erro
	public static function setError($msg)
	{
		$_SESSION[Order::ERROR] = $msg;
	}

	//pega o erro
	public static function getError()
	{
		$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';
		Order::clearError();
		return $msg;
	}

	//encerra a sess~]ao do erro
	public static function clearError()
	{
		$_SESSION[Order::ERROR] = NULL;
	}

	//inicia a sessão com da mesagem de sucesso
	public static function setSuccess($msg)
	{
		$_SESSION[Order::SUCCESS] = $msg;
	}

	//pega a mensagem de sucesso
	public static function getSuccess()
	{
		$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';
		Order::clearSuccess();
		return $msg;
	}

	//encerra a sessão da mensaem de sucesso
	public static function clearSuccess()
	{
		$_SESSION[Order::SUCCESS] = NULL;
	}

	//lista de pedidos
	public static function getPage($page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		");
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

	//lista de pedidos da busca
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :id OR f.desperson LIKE :search
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%',
			':id'=>$search
		]);
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}
}
?>