<?php

use Mpdf\Mpdf;

class PdfPrinter extends Mpdf {
    
    protected $context;
    
    public $_smarty;
    
    
    public function __construct(array $config = [], $container = null) 	{
		
        parent::__construct($config, $container);
        $this->context = Context::getContext();
        
        $this->_smarty = $this->context->smarty;
        
        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = new Configuration();
            
        }
        if (!isset($this->context->company)) {
            $this->context->company = new Company($this->context->phenyxConfig->get('EPH_COMPANY_ID'));
            
        }
        if (!isset($this->context->language)) {
            $this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT')))); 
        }
           
        
        if (!isset($this->context->translations)) {

            $this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
        }
        
	}
    
    public function createTemplate($tplName) {
        
        $path_parts = pathinfo($tplName);
        $tpl = '';
        if (file_exists($this->context->theme->path . 'pdf/' . $path_parts['filename'].'.tpl')) {
        
            $tpl = $this->context->theme->path . 'pdf/' . $path_parts['filename'].'.tpl';

        } else {
            $tpl = $tplName;
        }
        $extraTplPaths = $this->context->_hook->exec('actionCreatePdfTemplate', ['tplName' => $tplName], null, true);

        if (is_array($extraTplPaths)) {

            foreach ($extraTplPaths as $plugin => $template) {

                if (!is_null($template) && file_exists($template)) {
                    $tpl = $template;
                }

            }

        }
        
        if (file_exists($tpl)) {
            return $this->_smarty->createTemplate($tpl, $this->_smarty);
        }

    }
}