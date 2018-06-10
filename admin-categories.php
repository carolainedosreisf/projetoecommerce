<?php 
//arquivo com as rotas das categorias do admin

//classes
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;

//rota que é chamada pelo <a> do html 
//rota que é jogada na tela
$app->get("/admin/categories", function(){
	User::verifyLogin();//verifica se esta logado
	User::verifyLogin();
 
	//busca do html
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != '') {

		$pagination = Category::getPageSearch($search, $page);//metodo da classe category


    } else {

		$pagination = Category::getPage($page);//metodo da classe category

	}

	$pages = [];
	
	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, [
			'href'=>'/admin/users?'.http_build_query([
				'page'=>$x+1,
    			'search'=>$search
			]),
			'text'=>$x+1
		]);

	}
	$page = new PageAdmin();
 
 	$page->setTpl("categories", [ //html
		"categories"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
 	]);	
});

//rota que é jogada na tela
$app->get("/admin/categories/create", function(){
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("categories-create");	//html
});

//rota que é chamda pelo <a> do html
$app->post("/admin/categories/create", function(){
	User::verifyLogin();
	$category = new Category();
	$category->setData($_POST);//metodo da classe model
	$category->save();//metodo da classe category
	header('Location: /admin/categories');//rota que retornara
	exit;
});

//rota que é chamda pelo <a> do html
//rota que é jogada na tela
$app->get("/admin/categories/:idcategory/delete", function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);//metodo da classe category
	$category->delete();//metodo da classe category
	header('Location: /admin/categories');//rota que retornara apos excluir
	exit;
});

//rota que é jogada na tela
$app->get("/admin/categories/:idcategory", function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$page = new PageAdmin();
	$page->setTpl("categories-update", [//html
		'category'=>$category->getValues()//metodo da classe model
	]);	
});

//rota que é chamda pelo <a> do html
$app->post("/admin/categories/:idcategory", function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);//metodo da classe category
	$category->setData($_POST);//metodo da classe model
	$category->save();	//metodo da classe category
	header('Location: /admin/categories');//rota que retornara
	exit;
});

//rota que é jogada na tela
//rota que é chamada pelo <a> do html
$app->get("/admin/categories/:idcategory/products", function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);//metodo da classe product
	$page = new PageAdmin();
	$page->setTpl("categories-products", [//html
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(),//produtos relacionados com algumacategoria
		'productsNotRelated'=>$category->getProducts(false)//produtos não relacionados
	]);
});

//rota que é jogada na tela
//rota que é chamada pelo <a> do html
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);//metodo da classe category
	$product = new Product();
	$product->get((int)$idproduct);//metodo da classe category
	$category->addProduct($product);//metodo da classe category
	header("Location: /admin/categories/".$idcategory."/products");//rota que retronara
	exit;
});

//rota que é jogada na tela
//rota que é chamada pelo <a> do html
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);//metodo da classe category
	$product = new Product();
	$product->get((int)$idproduct);//metodo da classe category
	$category->removeProduct($product);//metodo da classe category
	header("Location: /admin/categories/".$idcategory."/products");//rota que retornara
	exit;
});
 ?>