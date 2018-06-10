<?php
//classes
use \Hcode\Model\User;
use \Hcode\Model\Cart;

//metodo que formata o valor do preço
function formatPrice( $vlprice){
    if(!$vlprice > 0) $vlprice = 0;
    return number_format((float)$vlprice, 2, ",", ".");
}

//metodo que formata a data
function formatDate($date)
{
	return date('d/m/Y', strtotime($date));
}

//metodo que checka o login
function checkLogin($inadmin =  true){
    return User::checkLogin($inadmin);
}


//função que pga o nome do usuario para mostrar no sessão da loja
function getUserName(){
    $user = User::getFromSession();//metodo da classe user
    return $user->getdesperson();
}

//função que que pega a quantidade de produtos do carrinho pra mostrar no icone do carrinho
function getCartNrQtd(){
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return $totals['nrqtd'];
}

//função que que pega o subtotal dos produtos do carrinho pra mostrar no icone do carrinho
function getCartVlSubTotal(){
    $cart = Cart::getFromSession();
    $totals = $cart->getProductsTotals();
    return formatPrice($totals['vlprice']);
}
?>
