<?php 
namespace Hcode\Model;//serve pra dizer onde esta a classe

//classes
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

//classe herdeira
class Cart extends Model {
	const SESSION = "Cart";//sessão do carrinho
	const SESSION_ERROR = "CartError";//constante do erro

	//qual o id da sessão?
	public static function getFromSession()
	{
		$cart = new Cart();

		//carrinho esta na sessão?
		if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
		} else {
			$cart->getFromSessionID();
			if (!(int)$cart->getidcart() > 0) {
				$data = [
					'dessessionid'=>session_id()
				];
				if (User::checkLogin(false)) {
					$user = User::getFromSession();//metodo da classe user
					
					$data['iduser'] = $user->getiduser();	
				}
				$cart->setData($data);//metodo localizado nesse propio arquivo
				$cart->save();//metodo localizado nesse propio arquivo
				$cart->setToSession();//metodo localizado nesse propio arquivo
			}
		}
		return $cart;
	}

	//verifica se a sessão esta aberta
	public function setToSession()
	{
		$_SESSION[Cart::SESSION] = $this->getValues();
	}

	//verifica se há rigistros no banco de dados
	public function getFromSessionID()
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			':dessessionid'=>session_id()
		]);
		if (count($results) > 0) {
			$this->setData($results[0]);//metodo da classe model
		}
	}	

	//metodo que seleciona a tabela do carrinho no banco de dados
	public function get($idcart)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
			':idcart'=>$idcart
		]);
		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	//salva os produtos no carrinho
	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
			':idcart'=>$this->getidcart(),
			':dessessionid'=>$this->getdessessionid(),
			':iduser'=>$this->getiduser(),
			':deszipcode'=>$this->getdeszipcode(),
			':vlfreight'=>$this->getvlfreight(),
			':nrdays'=>$this->getnrdays()
		]);
		$this->setData($results[0]);//metodo da classe model
	}

	//metodo que adiciona +1 produto especifico no carrinho
	public function addProduct(Product $product)
	{
		$sql = new Sql();
		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)", [
			':idcart'=>$this->getidcart(),
			':idproduct'=>$product->getidproduct()
		]);
		$this->getCalculateTotal();//metodo localizado nesse propio arquivo
	}

	//metodo que remove produtos do carrinhocarrinho
	public function removeProduct(Product $product, $all = false)
	{
		$sql = new Sql();
		//remove tudo
		if ($all) {
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);
			//remove 1(-)
		} else {
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);
		}
		$this->getCalculateTotal();//metodo localizado nesse propio arquivo
	}
	public function getProducts()
	{
		$sql = new Sql();
		$rows = $sql->select("
			SELECT b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct , b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
			ORDER BY b.desproduct
		", [
			':idcart'=>$this->getidcart()
		]);
		return Product::checkList($rows);//metodo da classe product
	}

	//calcular o total a pagar, incluindo todos os produtos que estais compramdo menos o frete
	public function getProductsTotals()
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products a
			INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
			WHERE b.idcart = :idcart AND dtremoved IS NULL;
		", [
			':idcart'=>$this->getidcart()
		]);
		if (count($results) > 0) {
			return $results[0];
		} else {
			return [];
		}
	}
	//calcula o frete
	public function setFreight($nrzipcode)
	{
		$nrzipcode = str_replace('-', '', $nrzipcode);
		$totals = $this->getProductsTotals();//metodo desse propio arquivo

		//se altura for menor que 2cm, ela vira 2cm que é o minimo permitido pelo calculo de frete
		//se o comprimento for menor que 16cm, ela vira 16cm que é o minimo permitido pelo calculo de frete
		if ($totals['nrqtd'] > 0) {
			if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
			if ($totals['vllength'] < 16) $totals['vllength'] = 16;

			//a direita são strings fixas e padronizadas determinadas pelos correios
			$qs = http_build_query([
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'09853120',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
			$result = $xml->Servicos->cServico;
			if ($result->MsgErro != '') {
				Cart::setMsgError($result->MsgErro);//metodo desse propio arquivo
			} else {
				Cart::clearMsgError();//metodo desse propio arquivo
			}
			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));//metodo desse propio arquivo
			$this->setdeszipcode($nrzipcode);
			$this->save();
			return $result;
		} else {
		}
	}

	//metodo para formatar os valores
	public static function formatValueToDecimal($value)
	{
		$value = str_replace('.', '', $value);
		return str_replace(',', '.', $value);
	}

	//inicia a sessão do erro
	public static function setMsgError($msg)
	{
		$_SESSION[Cart::SESSION_ERROR] = $msg;
	}

	//pega o erro
	public static function getMsgError()
	{
		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";
		Cart::clearMsgError();//metodo desse propio arquivo
		return $msg;
	}

	//limpa a sessão do erro
	public static function clearMsgError()
	{
		$_SESSION[Cart::SESSION_ERROR] = NULL;
	}

	//atualiza o frete automaticamente
	public function updateFreight()
	{
		if ($this->getdeszipcode() != '') {
			$this->setFreight($this->getdeszipcode());
		}
	}

	//esse metodo sob-escreve o original da classe pai
	public function getValues()
	{
		$this->getCalculateTotal();//metodo desse propio arquivo
		return parent::getValues();//metodo da classe pai(model)
	}

	//calcular o total a pagar, incluindo todos os produtos que estais compramdo mais o frete
	public function getCalculateTotal()
	{
		$this->updateFreight();
		$totals = $this->getProductsTotals();
		$this->setvlsubtotal($totals['vlprice']);
		$this->setvltotal($totals['vlprice'] + (float)$this->getvlfreight());
	}
}
 ?>