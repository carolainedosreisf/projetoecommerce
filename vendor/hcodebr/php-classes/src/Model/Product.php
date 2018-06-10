<?php 
namespace Hcode\Model;//serve para dizer onde a classe esta

//classes
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
class Product extends Model {
	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
	}

	//este metodo checka a lista de produtos
	public static function checkList($list)
	{
		foreach ($list as &$row) {
			
			$p = new Product();
			$p->setData($row);
			$row = $p->getValues();
		}
		return $list;
	}

	//metodo que salva os produtos
	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
			":idproduct"=>$this->getidproduct(),
			":desproduct"=>$this->getdesproduct(),
			":vlprice"=>$this->getvlprice(),
			":vlwidth"=>$this->getvlwidth(),
			":vlheight"=>$this->getvlheight(),
			":vllength"=>$this->getvllength(),
			":vlweight"=>$this->getvlweight(),
			":desurl"=>$this->getdesurl()
		));
		$this->setData($results[0]);
	}

	//metodo que seleciona a tabela dos produtos no banco de dados
	public function get($idproduct)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct'=>$idproduct
		]);
		$this->setData($results[0]);
	}

	//metodo que apaga os produtos
	public function delete()
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
			':idproduct'=>$this->getidproduct()
		]);
	}

	//update da imagem do produto
	public function checkPhoto()
	{
		if (file_exists(
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg"
			)) {
			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
	//se não existir a foto  ele trara uma foto padrão			
		} else {
			$url = "/res/site/img/product.jpg";
		}
		return $this->setdesphoto($url);
	}

	//sob-escrevendo o metodo que ja existia na class model
	public function getValues()
	{
		$this->checkPhoto();//medoto da classe desse propio arquivo
		$values = parent::getValues();
		return $values;
	}
	//metodo que transformara a imagem eno formato jpg
	public function setPhoto($file)
	{
		$extension = explode('.', $file['name']);
		$extension = end($extension);
		switch ($extension) {
			case "jpg":
			case "jpeg":
			$image = imagecreatefromjpeg($file["tmp_name"]);
			break;
			case "gif":
			$image = imagecreatefromgif($file["tmp_name"]);
			break;
			case "png":
			$image = imagecreatefrompng($file["tmp_name"]);
			break;
		}
		$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg";
		imagejpeg($image, $dist);
		imagedestroy($image);
		$this->checkPhoto();//medoto da classe desse propio arquivo
	}

	//metodo que busca a url correta
	public function getFromURL($desurl)
	{
		$sql = new Sql();
		$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
			':desurl'=>$desurl
		]);
		$this->setData($rows[0]);
	}
	/*metodo que ira mostar as categorias de um produto, ex: um produto especifico  ao clicar nele voce vera seus detalhes
	e tambem verá quais as categorias que ele pertence*/
	public function getCategories()
	{
		$sql = new Sql();
		return $sql->select("
			SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct
		", [
			':idproduct'=>$this->getidproduct()
		]);
	}

	//lista os produtos
	public static function getPage($page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products 
			ORDER BY desproduct
			LIMIT $start, $itemsPerPage;
		");
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

	//lista os produtos da busca
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products 
			WHERE desproduct LIKE :search
			ORDER BY desproduct
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