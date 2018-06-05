<?php
//este arquivo contem as totas do admin(funcionario)

//chama as classes

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Mailer;

//rota do admin que aparece na tela

//Se tiver logado vai para o index

$app->get('/admin', function() {
	User::verifyLogin();//metodo da classe user
	$page = new PageAdmin();
	$page->setTpl("index");

});
//rota do login que aparece na tela
$app ->get('/admin/login', function(){
	$page = new PageAdmin([
		//pagina sem header e footer
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("login");
});

//rota da ação do formulario do login

$app->post('/admin/login', function(){
	User::login($_POST["login"], $_POST["password"]);//metodo da classe user
	header("Location:/admin");//se login estiver certo vai para a rota get(/admin) 
	exit;
});


//rota que é jogada na tela
$app->get('/admin/logout', function() {

	User::logout();//usa esse metodo da classe User

	header("Location: /admin/login");
	exit;

});

//rota do forgot para digitar o email que é jogada na tela 
$app->get("/admin/forgot", function(){
	$page = new PageAdmin([
		//não possui header e footer
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot");//html
});

//rota que é chamado no <a> do html
$app->post("/admin/forgot", function(){
	$user = User::getForgot($_POST["email"]);//metodo da classe user
	header("Location: /admin/forgot/sent");//retorna para essa rota
	exit();
});

//rota do forgot dizendo que o email foi enviado jogando na tela
$app->get("/admin/forgot/sent", function(){
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-sent");//html
});


//rota para digitar nova senha que é jogada na tela
$app->get("/admin/forgot/reset", function(){
	$user = User::validForgotDecrypt($_GET["code"]);//criptografia
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-reset", array(//html
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});


/*rota que finaliza o processo e retorna pagina de sucesso em verde asserota */
$app->post("/admin/forgot/reset", function(){
	$forgot = User::validForgotDecrypt($_POST["code"]);//criptografia
	User::setForgotUsed($forgot["idrecovery"]);//metodo da classe user
	$user = new User();
	$user->get((int)$forgot["iduser"]);//metodo da classe user
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT,[//criptografia
		"cost"=>12
	]);
	$user->setPassword($password);//metodo da classe user
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-reset-success");//html
});

?>