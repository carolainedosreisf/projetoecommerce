<?php 
//este arquivo faz o CRUD, edita adiciona e exclui,  na parte dos usuarios que contem na pagina da administração

//chama as classes
use \Hcode\PageAdmin;
use \Hcode\Model\User;

//rota que é jogada na tela para alterar a senha
$app->get("/admin/users/:iduser/password", function($iduser){
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);//tetodo da classe user
	$page = new PageAdmin();
	$page->setTpl("users-password", [//html
		"user"=>$user->getValues(),//tetodo da classe user
		"msgError"=>User::getError(),//tetodo da classe user
		"msgSuccess"=>User::getSuccess()//tetodo da classe user
	]);
});

//rota que é pegado do formulario do admin para trocar a senha
$app->post("/admin/users/:iduser/password", function($iduser){
	User::verifyLogin();
	if (!isset($_POST['despassword']) || $_POST['despassword']==='') {
		User::setError("Preencha a nova senha.");//metodo da classe user
		header("Location: /admin/users/$iduser/password");//retornara pra essa rota em caso de erro
		exit;
	}
	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm']==='') {
		User::setError("Preencha a confirmação da nova senha.");//metodo da classe user
		header("Location: /admin/users/$iduser/password");//retornara pra essa rota em caso de erro
		exit;
	}
	if ($_POST['despassword'] !== $_POST['despassword-confirm']) {
		User::setError("Confirme corretamente as senhas.");//metodo da classe user
		header("Location: /admin/users/$iduser/password");//retornara pra essa rota em caso de erro
		exit;
	}
	$user = new User();
	$user->get((int)$iduser);
	$user->setPassword(User::getPasswordHash($_POST['despassword']));
	User::setSuccess("Senha alterada com sucesso.");//metodo da classe user
	header("Location: /admin/users/$iduser/password");//retornara pra essa rota em caso de sucesso
	exit;
});

//rota que joga na tela e acessa os usuarios existentes

$app->get("/admin/users", function() {
	User::verifyLogin();//se estiver logado execute daqui pra baixo:
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";//busca
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;//itens por pagina

	//busca no html
	if ($search != '') {
		$pagination = User::getPageSearch($search, $page);//metodo da classe User
	} else {
		$pagination = User::getPage($page);//metodo da classe User
	}
	$pages = [];
	for ($x = 0; $x < $pagination['pages']; $x++)
	{
		array_push($pages, [
			'href'=>'/admin/users?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search//busca
			]),
			'text'=>$x+1
		]);
	}
	$page = new PageAdmin();
	$page->setTpl("users", array(//usa esse template
		"users"=>$pagination['data'],//paginação
		"search"=>$search,//busca
		"pages"=>$pages
	));
});

//rota que é jogada na tela para criar um usuario
$app->get("/admin/users/create", function() {
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("users-create");//html
});

//rota que é jogada na tela e executa a ação para deletar um usuario
$app->get("/admin/users/:iduser/delete", function($iduser) {
	User::verifyLogin();	
	$user = new User();
	$user->get((int)$iduser);//medoto da classe user
	$user->delete();//metodo da class user
	header("Location: /admin/users");//html
	exit;
});

//rota que é jogada na tela para editar um usuario
$app->get("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);//metodo da classe User
	$page = new PageAdmin();
	$page->setTpl("users-update", array(//html
		"user"=>$user->getValues()//arquivo model
	));
});

//rota da ação do formulario
$app->post("/admin/users/create", function() {
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;//se for definido no formulario vale um se não é 0
	$_POST['despassword'] = User::getPassswordHash($_POST['despassword']);/*a senha é criptografada com o esse metodo da classe User */
	$user->setData($_POST);//arquivo model
	$user->save();//arquivo user
	header("Location: /admin/users");//rota get do mesmo
	exit;
});

//rota da ação do formulario
$app->post("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;//se for definido no formulario vale um se não é 0
	$user->get((int)$iduser);//metodo da classe User
	$user->setData($_POST);//arquivo model
	$user->update();//arquivo user
	header("Location: /admin/users");//rota get do mesmo
	exit;
});
 ?>