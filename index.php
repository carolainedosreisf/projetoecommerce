<?php 
//arquivo com todas as rotas do projeto, este é o arquivo que deve ser aberto para ver o html  inicial da loja
session_start();

require_once("vendor/autoload.php");//arquivo criado automaticamente com o comando composer update
use Slim\Slim;//slim chama os html's
use \Hcode\Page;//Page configura para cahmar dos html's do site(paginas do cliente)
use \Hcode\PageAdmin;//Page configura para cahmar dos html's do admin(paginas dos funcionarios)
use \Hcode\Model\User;
use \Hcode\Model\Category;

$app = new Slim();

$app->config('debug', true);

require_once("site.php");
require_once("functions.php");
require_once("admin.php");
require_once("admin-users.php");
require_once("admin-categories.php");
require_once("admin-product.php");
require_once("admin-orders.php");

$app->run(); //faz tudo funcionar



 ?>