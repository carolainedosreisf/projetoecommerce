<?php

//classes
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


//rota que é jogada na tela(editar status)
$app->get("/admin/orders/:idorder/status", function($idorder){
	User::verifyLogin();
	$order = new Order();
	$order->get((int)$idorder);//metodo da classe order
	$page = new PageAdmin();
	$page->setTpl("order-status", [//html
		'order'=>$order->getValues(),//metodo da classe order
		'status'=>OrderStatus::listAll(),//metodo da classe oderStatus
		'msgSuccess'=>Order::getSuccess(),//metodo da classe order
		'msgError'=>Order::getError()//metodo da classe order
	]);
});


//rota que é chamda pelo formulario para editar o status
$app->post("/admin/orders/:idorder/status", function($idorder){
	User::verifyLogin();
	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
		Order::setError("Informe o status atual.");//metodo da classe order
		header("Location: /admin/orders/".$idorder."/status");//rota que retornara em caso de erro
		exit;
	}
	$order = new Order();
	$order->get((int)$idorder);//metodo da classe order
	$order->setidstatus((int)$_POST['idstatus']);//metodo da classe order
	$order->save();//metodo da classe order
	Order::setSuccess("Status atualizado.");//metodo da classe order
	header("Location: /admin/orders/".$idorder."/status");//rota que retornara em caso de sucesso
	exit;
});

//rota que é jogada na tela e chamada pelo <a> do html (deleta pedido)
$app->get("/admin/orders/:idorder/delete", function($idorder){
	User::verifyLogin();
	$order = new Order();
	$order->get((int)$idorder);//metodo da classe order
	$order->delete();//metodo da classe order
	header("Location: /admin/orders");//rota que retornara
	exit;
});

//rota que é jogada na tela e chamada pelo <a> do html (ver detalhes do pedido)
$app->get("/admin/orders/:idorder", function($idorder){
	User::verifyLogin();
	$order = new Order();
	$order->get((int)$idorder);
	$cart = $order->getCart();
	$page = new PageAdmin();
	$page->setTpl("order", [//html
		'order'=>$order->getValues(),//metodo da classe order
		'cart'=>$cart->getValues(),//metodo da classe cart
		'products'=>$cart->getProducts()//metodo da classe cart
	]);
});

//rota que é jogada na tela e chamada pelo <a> do html (ver todos os pedidos)
$app->get("/admin/orders", function(){
	User::verifyLogin();
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	//busca do html
	if ($search != '') {
		$pagination = Order::getPageSearch($search, $page);//metodo da classe order
	} else {
		$pagination = Order::getPage($page);//metodo da classe order
	}
	$pages = [];
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, [
			'href'=>'/admin/orders?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
	}
	$page = new PageAdmin();
	$page->setTpl("orders", [//html
		"orders"=>$pagination['data'],
		"search"=>$search,//busca
		"pages"=>$pages//paginação
	]);
});
?>