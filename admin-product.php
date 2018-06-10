<?php 

//classes
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;

//rota que joga na tela
//rota que é chamada pelo <a> do html
$app->get("/admin/products", function(){
	User::verifyLogin();
	//busca e paginação	
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";//busca
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;//itens oir pagina

	//busca do html
	if ($search != '') {
		$pagination = Product::getPageSearch($search, $page);//metodo da classe product
	} else {
		$pagination = Product::getPage($page);//metodo da classe product
	}
	$pages = [];
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, [
			'href'=>'/admin/products?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
	}
	$products = Product::listAll();//metodo da classe product
	$page = new PageAdmin();
	$page->setTpl("products", [//html
		"products"=>$pagination['data'],
		"search"=>$search,//busca
		"pages"=>$pages//paginas
	]);
});

//rota que é jogada na tela
$app->get("/admin/products/create", function(){
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("products-create");//html
});

//rota que é chamda pelo formulario
$app->post("/admin/products/create", function(){
	User::verifyLogin();
	$product = new Product();
	$product->setData($_POST);//metodo da classe model
	$product->save();//motodo da classe product
	header("Location: /admin/products");//retornara para essa rota
	exit;
});

//rota que é jogada na tela
$app->get("/admin/products/:idproduct", function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);//motodo da classe product
	$page = new PageAdmin();
	$page->setTpl("products-update", [//html
		'product'=>$product->getValues()
	]);
});

//rota que é chamda pelo formulario 
$app->post("/admin/products/:idproduct", function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);//motodo da classe product
	$product->setData($_POST);//motodo da classe model
	$product->save();//motodo da classe product
	if($_FILES["file"]["name"] !== "")$product->setPhoto($_FILES["file"]); //motodo da classe product
	header('Location: /admin/products');//rota que retornara
	exit;
});

//rota que é puxado pelo <a> do html
//rota que é jogada na tela
$app->get("/admin/products/:idproduct/delete", function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);//motodo da classe product
	$product->delete();//motodo da classe product
	header('Location: /admin/products');//rota que retornara
	exit;
});
 ?>