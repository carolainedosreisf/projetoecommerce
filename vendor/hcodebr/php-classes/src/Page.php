<?php
//este arquivo possui a classe para configurar os templates do site(html visivel para o cliente)
namespace Hcode;//serve pra dizer onde a classe esta
    use Rain\Tpl;//chama a classe

    class Page{
        private $tpl;
        private $options = [];
        private $defaults = [
            //este argumentos definem a pagina header e footer com true por padrão
            "header"=>true,
            "footer"=>true,
            "data"=> []
        ];

        public function __construct($options = array(), $tpl_dir = "/views/"){//pasta do html

            $this->options = array_merge($this->defaults, $options);

            $config = array(
                "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"].$tpl_dir,
                "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",//pasta do caches do html
                "debug"         => false // comentarios para erros
               );
               Tpl::configure( $config );
               $this->tpl = new Tpl;
               $this->setData($this->options["data"]);

               //este if serve para evitar conflito com paginas que não tem header e footer

               if($this->options["header"]===true) $this->tpl -> draw("header");//desenha na tela o template header.html

        }

        private function setData($data = array()){
            foreach($data as $key =>$value){
                $this->tpl->assign($key, $value);
            }
        }

        //função com o conteudo do html, esse html varia de pagina para pagina
        public function setTpl($name, $data = array(), $returnHTML = false){
            $this->setData($data);
            return $this->tpl->draw($name, $returnHTML);
        }

        /*essa função possui o metodo destrutor e por isso não precisa ser informado nas rotas como o header e o conteudo*/
        public function __destruct(){
            if($this->options["footer"]===true) $this->tpl -> draw("footer");//desenha na tela o arquivo footer.html
            
        }
    }

?>