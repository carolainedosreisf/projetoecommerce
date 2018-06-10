<?php 
//arquivo com as rotas da loja em si

//classes
use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


$app->get('/', function() {
	$products = Product::listAll();//busca todos os produtos que estão no banco
	$page = new Page();
	$page->setTpl("index", [//html
		'products'=>Product::checkList($products)//metodo da classe product
	]);
});


//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->get("/categories/:idcategory", function($idcategory){
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$category = new Category();
	$category->get((int)$idcategory);
	$pagination = $category->getProductsPage($page);//metodo da classe category
	$pages = [];
	for ($i=1; $i <= $pagination['pages']; $i++) { 
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}
	$page = new Page();
	$page->setTpl("category", [//html
		'category'=>$category->getValues(),//metodo da classe product
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);
});

//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->get("/products/:desurl", function($desurl){
	$product = new Product();
	$product->getFromURL($desurl);//metodo da classe product
	$page = new Page();
	$page->setTpl("product-detail", [//html
		'product'=>$product->getValues(),//metodo da classe product
		'categories'=>$product->getCategories()//metodo da classe product
	]);
});

//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->get("/cart", function(){
	$cart = Cart::getFromSession();//metodo da classe cart
	$page = new Page();
	
	$page->setTpl("cart", [//html
		'cart'=>$cart->getValues(),//metodo da classe cart
		'products'=>$cart->getProducts(),//metodo da classe cart
		'error'=>$cart->getCartError()//metodo da classe cart
	]);
});

//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->get("/cart/:idproduct/add", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);//metodo da classe product
	$cart = Cart::getFromSession();//metodo da classe cart
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
	for ($i = 0; $i < $qtd; $i++) {
		
		$cart->addProduct($product);
	}
	header("Location: /cart");//retornara para essa rota
	exit;
});

//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->get("/cart/:idproduct/minus", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);//metodo da classe product
	$cart = Cart::getFromSession();//metodo da classe cart
	$cart->removeProduct($product);//metodo da classe cart
	header("Location: /cart");//retornara para essa rota
	exit;
});

//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->get("/cart/:idproduct/remove", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);//metodo da classe product
	$cart = Cart::getFromSession();//metodo da classe cart
	$cart->removeProduct($product, true);//metodo da classe cart
	header("Location: /cart");//retornara a essa rota
	exit;
});

//rota que é jogada na tela
//rota que é chamda pelo <a> do html
$app->post("/cart/freight", function(){
	$nrzipcode = str_replace("-", "", $_POST['zipcode']);
	$cart = Cart::getFromSession();//metodo da classe cart
	$cart->setFreight($nrzipcode);//metodo da classe cart
	header("Location: /cart");//rota que retornara
	exit;
});

//rota que é jogada na tela
$app->get("/checkout", function(){
	User::verifyLogin(false);//evita que o cliente acesse o login do admin
	$address = new Address();
	$cart = Cart::getFromSession();//metodo da classe cart
	if (isset($_GET['zipcode'])) {
		$_GET['zipcode'] = $cart->getdeszipcode();
	}
	if (isset($_GET['zipcode'])) {
		$address->loadFromCEP($_GET['zipcode']);//metodo da classe address
		$cart->setdeszipcode($_GET['zipcode']);//metodo da classecart
		$cart->save();//metodo da classe cart
		$cart->getCalculateTotal();//metodo da classe cart
	}
	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');
	if (!$address->getdestrict()) $address->setdesnumber('');
	$page = new Page();
	$page->setTpl("checkout", [//html
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),//metodo da classe address
		'products'=>$cart->getProducts(),//metodo da classe cart
		'error'=>Address::getMsgError()//metodo da classe address
	]);
});

//rota que é chamada pelo formulario
$app->post("/checkout", function(){
	User::verifyLogin(false);
	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");//metodo da classe address
		header('Location: /checkout');//rota que retornara
		exit;
	}
	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");//metodo da classeaddress
		header('Location: /checkout');//rota que retornara
		exit;
	}
	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");//metodo da classe address
		header('Location: /checkout');//rota que retornara
		exit;
	}
	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");//metodo da classe address
		header('Location: /checkout');//rota que retornara
		exit;
	}
	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");//metodo da classe address
		header('Location: /checkout');//rota que retornara
		exit;
	}
	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");//metodo da classe address
		header('Location: /checkout');//rota que retornara
		exit;
	}
	$user = User::getFromSession();//metodo da classe user
	$address = new Address();
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();
	$address->setData($_POST);
	$address->save();
	$cart = Cart::getFromSession();//metodo da classe cart
	$cart->getCalculateTotal();//metodo da classe cart
	$order = new Order();
	$order->setData([//metodo da classe order
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);
	$order->save();//metodo da classe order
	switch ((int)$_POST['payment-method']) {
		case 1:
		header("Location: /order/".$order->getidorder()."/pagseguro");
		break;
		case 2:
		header("Location: /order/".$order->getidorder()."/paypal");
		break;
	}
	exit;
});


//rota que é jogada na tela e que é pegada pelo <a> do html
$app->get("/order/:idorder/pagseguro", function($idorder){
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);//metodo da classe order
	$cart = $order->getCart();//metodo da classe order
	$page = new Page([
		'header'=>false,
		'footer'=>false
	]);
	$page->setTpl("payment-pagseguro", [
		'order'=>$order->getValues(),//metodo da classe order
		'cart'=>$cart->getValues(),//metodo da classe cart
		'products'=>$cart->getProducts(),//metodo da classe cart
		'phone'=>[
			'areaCode'=>substr($order->getnrphone(), 0, 2),
			'number'=>substr($order->getnrphone(), 2, strlen($order->getnrphone()))
		]
	]);
});

//rota que é jogada na tela e que é pegada pelo <a> do html
$app->get("/order/:idorder/paypal", function($idorder){
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);
	$cart = $order->getCart();
	$page = new Page([
		'header'=>false,
		'footer'=>false
	]);
	$page->setTpl("payment-paypal", [
		'order'=>$order->getValues(),//metodo da classe order
		'cart'=>$cart->getValues(),//metodo da classe cart
		'products'=>$cart->getProducts()//metodo da classe order
	]);
});

//rota que é jogada na tela
$app->get("/login", function(){;
	$page = new Page();
	$page->setTpl("login",[//html
		'error'=>User::getError(),//metodo da classe user
		'errorRegister'=>User::getErrorRegister(),//metodo da classe user
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);
	exit;
});


//rota que  é chamda pelo formulario
$app->post("/login", function(){
	try{
		User::login($_POST['login'], $_POST['password']);//metodo da classe user
	}catch(Exception $e){
		User::setError($e->getMessage());//metodo da classe user
	}
	header("Location: /checkout");//rota que retornara
	exit;
	
});


//rota que é jogada na tela 
//rota que é chamda pelo <a> do html

$app->get("/logout", function(){
	User::logout();//metodo da classe user
	header("Location:/login");//rota que retornara
	exit;
});

//rota que é chamda pelo formulario
$app->post("/register", function(){
	$_SESSION['registerValues'] = $_POST;/*sessão para não perder os dados digitados apos dar um erro */
	if (!isset($_POST['name']) || $_POST['name'] == '') {
		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;
	}
	if (!isset($_POST['email']) || $_POST['email'] == '') {
		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;
	}
	if (!isset($_POST['password']) || $_POST['password'] == '') {
		User::setErrorRegister("Preencha a senha.");
		header("Location: /login");
		exit;
	}
	if (User::checkLoginExist($_POST['email']) === true) {
		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;
	}
	$user = new User();
	$user->setData([//metodo da classe model
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);
	$user->save();//metodo da classe user
	User::login($_POST['email'], $_POST['password']);//metodo da classe user
	header('Location: /checkout');//retornara para essa rota
	exit;
});

//rota do forgot para digitar o email que é jogada na tela 
$app->get("/forgot", function() {
	$page = new Page();
	$page->setTpl("forgot1");	//HTML
});

//rota que é chamado no <a> do html
$app->post("/forgot", function(){
	$user = User::getForgot($_POST["email"], false);/*metodo da classe user, //o false evita que o cliente descubra a rota do admin pra recuperar a senha */
	header("Location: /forgot/sent");//rota que retornara
	exit;
});

//rota do forgot dizendo que o email foi enviado jogando na tela
$app->get("/forgot/sent", function(){
	$page = new Page();
	$page->setTpl("forgot-sent1");	//html
});

//rota para digitar nova senha que é jogada na tela
$app->get("/forgot/reset", function(){
	$user = User::validForgotDecrypt($_GET["code"]);//metodo da classe user
	$page = new Page();
	$page->setTpl("forgot-reset1", array(//html
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});

//rota para digitar nova senha que é jogada na tela
$app->post("/forgot/reset", function(){
	$forgot = User::validForgotDecrypt($_POST["code"]);	//metodo da classe user
	User::setForgotUsed($forgot["idrecovery"]);//metodo da classe user
	$user = new User();
	$user->get((int)$forgot["iduser"]);//metodo da classe user
	$password = User::getPasswordHash($_POST["password"]);//metodo da classe user
	$user->setPassword($password);//metodo da classe user
	$page = new Page();
	$page->setTpl("forgot-reset-success1");//html
});


//rota que é jogada na tela
$app->get("/profile", function(){
    User::verifyLogin(false);//false evita que o cliente acesse o login do admin
    $user= User::getFromSession();//metodo da classe user
    $page = new Page();

    $page->setTpl("profile",[//html
        'user'=>$user->getValues(),
        'profileMsg'=>User::getSuccess(),//Metodo da classe user
        'profileError'=>User::getError()//metodo da classe user
    ]);
});


//rota que é chamada pelo <a> do html
$app->post("/profile", function(){
	User::verifyLogin(false);//false evita que o cliente acesse o login do admin
	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		$_SESSION[User::SESSION] = $user->getValues(); 
		User::setError("Preencha o seu nome.");//Metodo da classe user
		header('Location: /profile');//rota que retornara
		exit;
	}
	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Preencha o seu e-mail.");//Metodo da classe user
		header('Location: /profile');//rota que retornara
		exit;
	}
	$user = User::getFromSession();
	//se o endereço de email for editado
	if ($_POST['desemail'] !== $user->getdesemail()) {
		//verifica se esse email já esta sendo usado
		if (User::checkLoginExist($_POST['desemail']) === true) {
			User::setError("Este endereço de e-mail já está cadastrado.");//Metodo da classe user
			header('Location: /profile');//rota que retornara
			exit;
		}
	}
	$_POST['iduser'] = $user->getiduser(); 
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];
	$user->setData($_POST);
	$user->update();//metodo da classe user
	$_SESSION[User::SESSION] = $user->getValues();
	User::setSuccess("Dados alterados com sucesso!");//metodo da classe user
	header('Location: /profile');//rota que retornara
	exit;
});


//rota que é jogada na tela
$app->get("/order/:idorder", function($idorder){
	User::verifyLogin(false);//o cliente não consegui entrar no login do admin
	$order =  new Order();
	$order->get((int)$idorder);//metodo da classe order
	$page = new Page();
	$page->setTpl("payment",[//html
		'order'=>$order->getValues()

	]);
});

//rota que é jogada na tela 
//rota que é puxada pelo <iframe> do html
$app->get("/boleto/:idorder", function($idorder){
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);//metodo da classe order
	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula
	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();
	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";
	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";
	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //
	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta
	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157
	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";
	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
});


//rota que é jogada na tela
//rora que é chamada pelo <a> do html
$app->get("/profile/orders", function(){
	User::verifyLogin(false);
	$user = User::getFromSession();//metodo da classe user
	$page = new Page();
	$page->setTpl("profile-orders",[//html
		'orders'=>$user->getOrders(),
	]);
});

//rota que é jogada na tela
//rora que é chamada pelo <a> do html
$app->get("/profile/orders/:idorder",function($idorder){
	User::verifyLogin(false);
	$order = new order();
	$order->get((int)$idorder);
	$cart = new Cart();
	$cart->get((int)$order->getidcart());
	$cart->getCalculateTotal();
	$page = new Page();
	$page->setTpl("profile-orders-detail",[//html
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
		
	]);
});

//rota que é jogada na tela
$app->get("/profile/change-password", function(){
	User::verifyLogin(false);
	$page = new Page();
	$page->setTpl("profile-change-password",[
		'changePassError'=>User::getError(),//metodo da classe user
		'changePassSuccess'=>User::getSuccess()//metodo da classe user
	]);
});

//rora que é chamada pelo formulario do html
$app->post("/profile/change-password", function(){
	User::verifyLogin(false);
	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {
		User::setError("Digite a senha atual.");//metodo da classe user
		header("Location: /profile/change-password");//rota que retornara caso de erro
		exit;
	}
	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {
		User::setError("Digite a nova senha.");//metodo da classe user
		header("Location: /profile/change-password");//rota que retornara caso de erro
		exit;
	}
	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {
		User::setError("Confirme a nova senha.");//metodo da classe user
		header("Location: /profile/change-password");//rota que retornara caso de erro
		exit;
	}
	if ($_POST['current_pass'] === $_POST['new_pass']) {
		User::setError("A sua nova senha deve ser diferente da atual.");//metodo da classe user
		header("Location: /profile/change-password");//rota que retornara caso de erro
		exit;		
	}
	if ($_POST['new_pass'] != $_POST['new_pass_confirm']) {
 
		User::setError("A senha de confirmação deve ser igual a nova senha.");
		header("Location: /profile/change-password");
		exit;
		}
	$user = User::getFromSession();//metodo da classe user
	if (!password_verify($_POST['current_pass'], $user->getdespassword())) {
		User::setError("A senha está inválida.");//metodo da classe user
		header("Location: /profile/change-password");//rota que retornara caso de erro
		exit;			
	}
	$user->setdespassword($_POST['new_pass']);//metodo da classe user
	$user->update();//metodo da classe user
	$_SESSION[User::SESSION] = $user->getValues();
	User::setSuccess("Senha alterada com sucesso.");//metodo da classe user
	header("Location: /profile/change-password");//rota que retorna em caso de sucesso
	exit;
});
 ?>




 ?>