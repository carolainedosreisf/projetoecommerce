<?php 

namespace Hcode\Model;//serve pra dizer onde a classe esta

//chama as classes
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

//essa classe herda do pai Model

class User extends Model {
//constantes
	const SESSION = "User";//constante da sessão
	const SECRET = "HcodePhp7_Secret";//constante para descriptografar o link recebido por email
	const ERROR = "UserError";//constante do erro do login
	const ERROR_REGISTER = "UserErrorRegister";//constante do erro do cadastro
	const SUCCESS = "UserSucesss";//constante do sucesso

	public static function getFromSession()
	{

		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {

			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;

	}
//metodo para checar o login, este metodo tem relação com o metodo verifylogin
	public static function checkLogin($inadmin = true)
	{
		//se não existir tudo isso ele não loga
		if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]//constante definida no inicio do codigo
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;

		} else {
			//se ele for um usuario admiistrador ele entra no html do admin
			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {

				return true;
				//esta logado mais não é um administrador
			} else if ($inadmin === false) {

				return true;
				//esta logado e é um administrador				
			} else {

				return false;

			}

		}

	}
	//verifica o login e inicia a sessão
	public static function login($login, $password)
	{

		$sql = new Sql();//faz a conexão com o banco de dados(class.php)


		//seleciona o banco de dados
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		)); 

		//se ele não encontar nenhum resultado de ":LOGIN"=>$login:

		if (count($results) === 0)
		{
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}

		$data = $results[0];

		//se o login for encontrado e senha tambem corresponder a sessão inicia

		if (password_verify($password, $data["despassword"]) === true)
		{

			$user = new User();

			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();//constante que esta no inicio do arquivo

			return $user;

		//caso contrario não inicia

		} else {
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}

	}

		//metodo que verifica se o usuario esta logado e se a sessão foi iniciada

	public static function verifyLogin($inadmin = true)//o usuario tem que ser admin, para acessar esse html
	{
		//se não tiver logado vai para /admin/login
		if (!User::checkLogin($inadmin)) {//metodo criado dentro deste propio arquivo

			if ($inadmin) {
				header("Location: /admin/login");

			//se tiver logado vai para /admin
				
			} else {
				header("Location: /login");
			}
			exit;

		}

	}

	//metodo que desroi a sessão

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

	}


	//metodo que salva no banco de dados um novo usuario
	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>utf8_decode($this->getdesperson()),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()),//metodo desse propio arquivo
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}
	//metodo que seleciona um usuario especifico
	public function get($iduser)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser"=>$iduser
		));

		$data = $results[0];

		$data['desperson'] = utf8_encode($data['desperson']);


		$this->setData($data);

	}

	//metodo que edita os usuarios
	public function update()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>utf8_decode($this->getdesperson()),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>User::getPasswordHash($this->getdespassword()),//metodo desse propio arquivo
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);		

	}
	//metodo para exluir usuarios
	public function delete()
	{

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));

	}
	//metodo que envia o email para recuperar a senha
	public static function getForgot($email, $inadmin = true)
	{
		//faz a conexão com o banco de dados
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT *
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(
			":email"=>$email//buca se o email existe
		));
		//se não existe:
		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
			
		}
		//se existe envia um dado e executa a procedure veja se o dado  ainda é valido:
		else
		{

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			//se não for valido:

			if (count($results2) === 0)
			{

				throw new \Exception("Não foi possível recuperar a senha");

			}
			//se for valida envia o email:
			else
			{

				$dataRecovery = $results2[0];

				$code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));//encripta esse link
				//se for um admin
				if ($inadmin === true) {
					
					$link = "http://localhost:3000/admin/forgot/reset?code=$code";
					//se for um cliente
				} else {

					$link = "http://localhost:3000/forgot/reset?code=$code";

				}

				//usa o mailer pra enviar o enail
				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha da Hcode Store", "forgot", array(
					"name"=>$data["desperson"],
					"link"=>$link
				));

				$mailer->send();

				return $data;

			}


		}

	}

	//metodo para encriptar o link para mudar a senha via email
	public static function validForgotDecrypt($code)
	{

		$idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE 
				a.idrecovery = :idrecovery
			    AND
			    a.dtrecovery IS NULL
			    AND
			    DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{

			return $results[0];

		}

	}
	//troca a senha via email
	public static function setFogotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));

	}
	//troca a senha via hmtl
	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));

	}
	//inicia a sessão do errro
	public static function setError($msg)
	{

		$_SESSION[User::ERROR] = $msg;

	}
	//PEGA O ERRO
	public static function getError()
	{

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;

	}
	//encerra a sessão do erro
	public static function clearError()
	{

		$_SESSION[User::ERROR] = NULL;

	}

	//cria a sessão com mensagem de sucesso
	public static function setSuccess($msg)
	{

		$_SESSION[User::SUCCESS] = $msg;

	}

	//pega a mesagem de sucesso
	public static function getSuccess()
	{

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		User::clearSuccess();

		return $msg;

	}

		//limpa a sessão com mensagem de sucesso
	public static function clearSuccess()
	{

		$_SESSION[User::SUCCESS] = NULL;

	}

	//inicia a sessão do erro
	public static function setErrorRegister($msg)
	{

		$_SESSION[User::ERROR_REGISTER] = $msg;

	}

	//pega o erro
	public static function getErrorRegister()
	{

		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;

	}

	//encerra a sessão do erro
	public static function clearErrorRegister()
	{

		$_SESSION[User::ERROR_REGISTER] = NULL;

	}

	//CHECKA SE O LOGIN JA EXISTE PARA EVITAR DE CADASTRAR DOIS IGUAIS OU ATE MAIS
	public static function checkLoginExist($login)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
			':deslogin'=>$login
		]);

		return (count($results) > 0);

	}

	//metodo para encriptar a senha
	public static function getPasswordHash($password)
	{

		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);

	}

	//seleciona a tabela de pedidos no banco de dados
	public function getOrders()
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
			WHERE a.iduser = :iduser
		", [
			':iduser'=>$this->getiduser()
		]);

		return $results;

	}

	//lista os usuarios
	public static function getPage($page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			ORDER BY b.desperson
			LIMIT $start, $itemsPerPage;
		");

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
 
	}

	//lista os usuarios da busca
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{

		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();

		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson)
			WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
			ORDER BY b.desperson
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%'
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