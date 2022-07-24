<?php
if (!defined('_PS_VERSION_'))
	exit;

include_once(_PS_ADMIN_DIR_.'/../config/config.inc.php');


class Shopinvoices extends Module
{

	public function __construct()
	{
		$this->bootstrap = true;
		$this->name = 'shopinvoices';
		$this->tab = 'administration';
		$this->version = '1.0.0';
		$this->author = 'Joan Melis';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7');
		$this->dependencies = null;

		parent::__construct();

		$this->displayName = $this->l('Download invoices');
		$this->description = $this->l('Download invoices and status per month at csv');

		$this->confirmUninstall = $this->l('Seguro que quieres desinstalar?');

	}

	public function install()
	{
		if (!parent::install()){
			return false;
		}
		return true;
	}

	public function uninstall()
	{
	   if (!parent::uninstall()) {
	     return false;
	   }
	   return true;
	}

	public function getContent()
	{
		$output = null;
		
		if(Tools::isSubmit('descargaFacturas')){
			$dateIni = strval(Tools::getValue('dateIni'));
			$monthFin = strval(Tools::getValue('dateFin'));
			$dateFin = date("Y-m-t", strtotime($monthFin));

			$query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS("SELECT SQL_CALC_FOUND_ROWS a.`id_order`, Concat( c.`firstname`, ' ', c.`lastname` ) AS `client`, Concat( 'FA00', a.`invoice_number` ) AS `id_invoice`, ROUND( `total_paid_tax_excl`, 2 ) AS `total_tax_excl`, ROUND( `total_paid_tax_incl`, 2 ) AS `total_tax_incl`, a.`date_add` AS `date`, osl.`name` AS `status` FROM `" . _DB_PREFIX_ . "orders` a LEFT JOIN `" . _DB_PREFIX_ . "customer` c ON ( c.`id_customer` = a.`id_customer` ) INNER JOIN `" . _DB_PREFIX_ . "address` address ON address.id_address = a.id_address_delivery LEFT JOIN `" . _DB_PREFIX_ . "order_state` os ON ( os.`id_order_state` = a.`current_state` ) LEFT JOIN `" . _DB_PREFIX_ . "order_state_lang` osl ON ( os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = 1 ) WHERE a.date_add BETWEEN '".$dateIni."-01 00:00:00' AND '".$dateFin." 23:59:00' and a.invoice_number != 0 ORDER BY a.date_add ASC");

			if($query){
			    $delimiter = ";";
			    $filename = "invoices_".$dateIni."_". $monthFin . ".csv";
			    
			    $f = fopen('php://memory', 'w');
			    fputs($f, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
			    $fields = array('id_order', 'client', 'id_invoice', 'total_tax_excl', 'total_tax_incl', 'date', 'status');
			    fputcsv($f, $fields, $delimiter);
			    
			    foreach ($query as $factura) {
	        		$lineData = array($factura['id_order'], $factura['client'], $factura['id_invoice'], $factura['total_tax_excl'], $factura['total_tax_incl'], $factura['date'], $factura['status']);
	        		fputcsv($f, $lineData, $delimiter);
	    		}
			    
			    fseek($f, 0);
			    
			    header('Content-Type: text/csv charset=utf-8');
			    header('Content-Disposition: attachment; filename="' . $filename . '";');
			    
			    fpassthru($f);
			     exit();
			}
		}

		if(Tools::isSubmit('descargaAbono')){
			$dateAIni = strval(Tools::getValue('dateAIni'));
			$monthAFin = strval(Tools::getValue('dateAFin'));
			$dateAFin = date("Y-m-t", strtotime($monthAFin));

			$query = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS("SELECT o.id_order AS `id`, Concat( c.`firstname`, ' ', c.`lastname` ) AS `name`, Concat( 'AB0000', o.`id_order_slip` ) AS `id_invoice`, ROUND( o.`total_products_tax_excl`, 2 ) AS `total_tax_excl`, ROUND( o.`total_products_tax_incl`, 2 ) AS `total_tax_incl`, o.`date_add` AS `date` FROM `" . _DB_PREFIX_ . "orders` a LEFT JOIN `" . _DB_PREFIX_ . "customer` c ON ( c.`id_customer` = a.`id_customer` ) INNER JOIN `" . _DB_PREFIX_ . "address` address ON address.id_address = a.id_address_delivery INNER JOIN `" . _DB_PREFIX_ . "order_slip` o ON ( o.`id_order` = a.`id_order` ) WHERE a.id_shop IN ( 1 ) AND o.date_add BETWEEN '".$dateAIni."-01 00:00:00' AND '".$dateAFin." 23:59:00' ORDER BY o.date_add ASC");

			if($query){
			    $delimiter = ";";
			    $filename = "Abonos_".$dateAIni."_". $monthAFin . ".csv";
			    
			    $f = fopen('php://memory', 'w');
			    fputs($f, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
			    
			    $fields = array('name', 'id_invoice', 'total_tax_excl', 'total_tax_incl', 'date');
			    fputcsv($f, $fields, $delimiter);
			    
			    foreach ($query as $factura) {
	        		$lineData = array($factura['name'], $factura['id_invoice'], $factura['total_tax_excl'], $factura['total_tax_incl'], $factura['date']);
	        		fputcsv($f, $lineData, $delimiter);
	    		}
			    
			    fseek($f, 0);
			    
			    header('Content-Type: text/csv charset=utf-8');
			    header('Content-Disposition: attachment; filename="' . $filename . '";');
			    
			    fpassthru($f);
			     exit();
			}
		}

		return $output.$this->displayForm();
	}

	 public function getconf() {
        $var = new stdClass();
        $var->date = Configuration::get('date');
        return $var;
    }
	public function displayForm(){
		
		$r = '<div class="bootstrap">';
		$var = $this->getconf();		
		$textHIW = $this->l("");
		$r.='
		<div class="panel">
			<h3>'.$this->l("Gestor facturas").'</h3>
			<p>'.$textHIW.'</p>
			<div class="clearfix"></div>
			<label>Rango de facturas:</label><br>
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
				De <input type="month" name="dateIni" placeholder="Mes de inicio" value="'.date("Y-m",strtotime(date("Y-m")."- 2 month")).'" min="2018-05"> a
				<input type="month" name="dateFin" placeholder="Mes de fin" value="'.date("Y-m").'" max="'.date("Y-m").'">
				<input type="submit" name="descargaFacturas" class="btn btn-info" value="Descargar datos">
				<br>
				<hr>
				<label>Facturas por abono:</label>
				<br>
				De <input type="month" name="dateAIni" placeholder="Mes de inicio" value="'.date("Y-m",strtotime(date("Y-m")."- 2 month")).'" min="2018-05"> a
				<input type="month" name="dateAFin" placeholder="Mes de fin" value="'.date("Y-m").'" max="'.date("Y-m").'">
				<input type="submit" name="descargaAbono" class="btn btn-info" value="Descargar Abono">
			</form>
			
		</div>';
		return $r;
	}

}



