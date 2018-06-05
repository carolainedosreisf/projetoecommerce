<?php 
//este arquivo possui a classe para configurar os templates do admin(html visivel para o funcionario)

namespace Hcode;//serve pra dizer onde a classe esta

class PageAdmin extends Page{//esta classe herda do seu pai page
    public function __construct($opts = array(), $tpl_dir = "/views/admin/"){//pasta do html
        parent::__construct($opts, $tpl_dir);
    }
}
 
?>