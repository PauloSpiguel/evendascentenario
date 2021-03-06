<?php
session_start();
require_once "vendor/autoload.php";
//require_once "vendor/hcodebr/php-classes/src/DB/SecretAdmin.php";
//Tras as dependencias do sistema

use \Hcode\Model\User; //namespaces de usuarios
use \Hcode\Page; //namespaces Página
use \Hcode\PageAdmin; //namespaces Admin
use \Slim\Slim;
use \Hcode\Model\Category;

$app = new Slim(); //rotas

$app->config('debug', true);

############## CONFIGURAÇÃO DE ROTA INDEX ###################
$app->get('/', function () {
//Metodo para contrução dos modelos->Method to build the templates

    $page = new Page(); //Cria o header da página->Create the page header

    $page->setTpl("index"); //Carrega o conteudo

});

############## CONFIGURAÇÃO DE ROTA ADMIN ###################
$app->get('/admin', function () {
//Metodo para contrução dos modelos->Method to build the templates

    User::verifyLogin(); //Verifica se o usuario está logado

    $page = new PageAdmin(); //Cria o header da página->Create the page header

    $page->setTpl("index"); //Carrega o conteudo

});

################## ROTA LOGIN ##################################
$app->get('/admin/login', function () {

    $page = new PageAdmin([
        //Desabilita o carregamento padrão
        'header' => false, //disable the loading default header
        'footer' => false, //disable the loading footer default
    ]);

    $page->setTpl("login");
});

################## ROTA VALIDAÇÃO LOGIN ##################################
$app->post('/admin/login', function () {

    User::login($_POST["login"], $_POST["password"]);

    header("location: /admin"); //Redirect to home page

    exit;

});
################## ROTA LOGOUT ######################
$app->get('/admin/logout', function () {

    User::logout();
    header("Location: /admin/login");
    exit;
});

################## ROTA LISTAR USUARIOS ########################
$app->get('/admin/users', function () {

    User::verifyLogin(); //Verifica se o usuario esta logado e se é administrativo
    $users = User::listAll(); // Carrega lista de usuários

    $page = new PageAdmin(); //Carrega templete na tela de header e footer

    $page->setTpl("users", array(
        "users" => $users,
    )); //Carrega conteúdo da tela no templete
});

################## ROTA CREATE USUÁRIO ########################
$app->get('/admin/users/create', function () {

    User::verifyLogin(); //Verifica se o usuario esta logado e se é administrativo

    $page = new PageAdmin(); //Carrega templete na tela de header e footer

    $page->setTpl("users-create"); //Carrega conteúdo da tela
});

###### FUNÇÃO DELETE USUARIO ###############
//Teve estar acima da função de altera usuário para evitar conflito
$app->get('/admin/users/:iduser/delete', function ($iduser) {

    User::verifyLogin();

    $user = new User();

    $user->get((int) $iduser);

    $user->delete();

    header('Location: /admin/users');

    exit;
});

################## ROTA ALTERA USUÁRIO ########################
$app->get('/admin/users/:iduser', function ($iduser) {

    User::verifyLogin(); //Verifica se o usuario esta logado e se é administrativo

    $user = new User();

    $user->get((int) $iduser);

    $page = new PageAdmin(); //Carrega templete na tela de header e footer

    $page->setTpl("users-update", array(
        "user" => $user->getValues(),
    )); //Carrega conteúdo da tela

});
################## CRIA NOVO USUÁRIO ########################
$app->post('/admin/users/create', function () {
//Cria o cliente
    User::verifyLogin();

    $user = new User(); //Cria novo usuário

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0; //Condição verificação de valor

    $_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, ["cost" => 12,
    ]);

    $user->setData($_POST);

    $user->save(); //Executa insert no banco de dados

    header('Location: /admin/users');

    exit;

});
################## ALTERA USUÁRIO ########################
$app->post('/admin/users/:iduser', function ($iduser) {

    User::verifyLogin();

    $user = new User();

    $user->get((int) $iduser); //Select no db

    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0; //Condição verificação de valor

    $user->setData($_POST); //Cria os Gets e Sets

    $user->update();

    header('Location: /admin/users');

    exit;

});
################## ROTA RECUPERA SENHA USUÁRIO ########################
$app->get("/admin/forgot", function () {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false,
    ]);

    $page->setTpl("forgot");

});
################## ENVIA EMAIL USUÁRIO ########################
$app->post("/admin/forgot", function () {

    $user = User::getForgot($_POST["email"]);

    header("Location: /admin/forgot/sent");

    exit;

});
################## CARREGA PÁGINA DE ENVIO DE RECUPERA SENHA EMAIL ########################
$app->get("/admin/forgot/sent", function () {

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-sent");

});
################## CARREGA PÁGINA PARA RESETAR SENHA ########################
$app->get("/admin/forgot/reset", function () {

    $user = User::validForgotDecrypt($_GET["code"]);//Metodo para decriptografar o código

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset", array(
        "name"=>$user["desperson"],//passa a variavel desperson para o templete
        "code"=>$_GET["code"]//passa a variavel code para o templete
    ));

});
################## ROTA PARA TROCAR SENHA E CARREGAR MSM DE SUCESSO NA TELA ########################
$app->post("/admin/forgot/reset", function(){

    $forgot = User::validForgotDecrypt($_POST['code']);

    User::setForgotUser($forgot["idrecovery"]);//metodo para 

    $user = new User();

    $user->get((int)$forgot["iduser"]);//pega o id do usuário 

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT, [

        "cost"=>12//nivel de processamento de segurança
    ]);

    $user->setPassword($password);//metodo para atualizar o senha no banco

    $page = new PageAdmin([
        "header" => false,
        "footer" => false
    ]);

    $page->setTpl("forgot-reset-success"); 

});
################## ROTA LISTAR CATEGORIAS ########################
$app->get("/admin/categories", function(){

    User::verifyLogin();

    $categories = Category::listAll();

    $page = new PageAdmin();

    $page->setTpl("categories", [
        'categories' => $categories //Envia para o templete categorias
    ]);

});
################## ROTA CREATE CATEGORIAS ########################
$app->get("/admin/categories/create", function(){

    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("categories-create");

});
################## PASSA DADOS CREATE CATEGORIAS ########################
$app->post("/admin/categories/create", function(){

    User::verifyLogin();

    $category = new Category();

    $category->setData($_POST);//Adiciona os valores de formulario no objeto

    $category->save();

    header("Location: /admin/categories");

    exit;

});
################## ROTA DELETE CATEGORIAS ########################
$app->get("/admin/categories/:idcategory/delete", function($idcategory){

    User::verifyLogin();

    $category = new Category(); //Nova instancia de classe

    $category->get((int)$idcategory); //Carrega objeto verificando se ainda existe no banco de dados

    $category->delete();

    header("Location: /admin/categories");

    exit();

});
################## ROTA EDITAR CATEGORIAS ########################
$app->get("/admin/categories/:idcategory", function($idcategory){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $page = new PageAdmin();

    $page->setTpl("categories-update", [
        "category" => $category->getValues()
    ]);

});
################## ROTA EDITAR/SAVE CATEGORIAS ########################
$app->post("/admin/categories/:idcategory", function($idcategory){

    User::verifyLogin();

    $category = new Category();

    $category->get((int)$idcategory);

    $category->setData($_POST);

    $category->save();

    header("Location: /admin/categories");

    exit;

});
$app->run();
