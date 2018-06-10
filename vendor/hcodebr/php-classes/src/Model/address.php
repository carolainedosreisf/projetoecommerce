<?php 
namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
class Address extends Model {
	const SESSION_ERROR = "AddressError";
	public static function getCEP($nrcep)
	{
		$nrcep = str_replace("-", "", $nrcep);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);
		return $data;
	}
	public function loadFromCEP($nrcep)
	{
		$data = Address::getCEP($nrcep);
		if (isset($data['logradouro']) && $data['logradouro']) {
			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep); 
		}
	}

	//salva o endreço da compra no banco de dados
	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [
			':idaddress'=> $this->getidaddress(),
			':idperson'=> $this->getidperson(),
			':desaddress'=> $this->getdesaddress(),
			':desnumber'=> $this->getdesnumber(),
			':descomplement'=> $this->getdescomplement(),
			':descity'=> $this->getdescity(),
			':desstate'=> $this->getdesstate(),
			':descountry'=> $this->getdescountry(),
			':deszipcode'=> $this->getdeszipcode(),
			':desdistrict'=> $this->getdesdistrict()
		]);
		if (count($results) > 0) {
			$this->setData($results[0]);//metodo da classe pai(model)
		}
	}
	//inicia a sessão do erro
	public static function setMsgError($msg)
	{
		$_SESSION[Address::SESSION_ERROR] = $msg;
	}
	//pega o erro
	public static function getMsgError()
	{
		$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";
		Address::clearMsgError();
		return $msg;
	}
	//limpa a sessão do erro
	public static function clearMsgError()
	{
		$_SESSION[Address::SESSION_ERROR] = NULL;
	}
}
 ?>