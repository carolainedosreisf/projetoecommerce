<?php 
//classes
namespace Hcode\Model;
use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
class Category extends Model {
	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
	}

	//metodo que salva a as categorias
	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
			":idcategory"=>$this->getidcategory(),
			":descategory"=>$this->getdescategory()
		));
		$this->setData($results[0]);
		Category::updateFile();//metodo desse propio arquivo
	}

	//seleciona a tabela de categorias no banco
	public function get($idcategory)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory'=>$idcategory
		]);
		$this->setData($results[0]);
	}

	//metodo para apgar a categoria
	public function delete()
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
			':idcategory'=>$this->getidcategory()
		]);
		Category::updateFile();//metodo desse propio arquivo
	}

	//metodo que joga no html da loja as categorias no footer dinamicamente
	public static function updateFile()
	{
		$categories = Category::listAll();
		$html = [];
		foreach ($categories as $row) {
			array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
		}
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
	}

	//Tras todos os produtos do banco de dados
	public function getProducts($related = true)
	{
		$sql = new Sql();
		//produtos que estão relacionados com a categoria
		if ($related === true) {
			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct IN(
					SELECT a.idproduct
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				':idcategory'=>$this->getidcategory()
			]);

		//produtos que não estão relacionados com a categoria
			
		} else {
			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct NOT IN(
					SELECT a.idproduct
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				':idcategory'=>$this->getidcategory()
			]);
		}
	}

	//paginação, itens por pagina
	public function getProductsPage($page = 1, $itemsPerPage = 8)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itemsPerPage;
		", [
			':idcategory'=>$this->getidcategory()
		]);
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		return [
			'data'=>Product::checkList($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

	//metodo que adiciona o produto a categoria
	public function addProduct(Product $product)
	{
		$sql = new Sql();
		$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct)", [
			':idcategory'=>$this->getidcategory(),
			':idproduct'=>$product->getidproduct()
		]);
	}

	//metodo que remove o produto da categoria
	public function removeProduct(Product $product)
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
			':idcategory'=>$this->getidcategory(),
			':idproduct'=>$product->getidproduct()
		]);
	}
		//lista todas as categorias	
	public static function getPage($page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			ORDER BY descategory
			LIMIT $start, $itemsPerPage;
		");
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
		];
	}

	//lista as categorias da busca
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
	{
		$start = ($page - 1) * $itemsPerPage;
		$sql = new Sql();
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			WHERE descategory LIKE :search
			ORDER BY descategory
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
