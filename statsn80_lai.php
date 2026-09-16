<?php

/**
  * Statistics
  * @category stats 
  * Licence: Commercial (Version 3.0 for prestashop 1.6.x)
  */
  
class statsn80_lai extends ModuleGrid
{
	private $_html = null;
	private $_query =  null;
	private $_columns = null;
	private $_defaultSortColumn = null;
	private $_emptyMessage = null;
	private $_pagingMessage = null;
	private $_ProductRef = null;
	private $_sTaxEachProduct = '0';
	
	function __construct()
	{
		$this->name = 'statsn80_lai';
		$this->tab = 'analytics_stats';
		$this->version = 3.0;
		$this->author = 'N80-POS';
		$this->_ProductRef = '#statsLN_ps16_com';
		
		$this->_defaultSortColumn = 'id_order';
		$this->_emptyMessage = $this->l('no rows returned');
		$this->_pagingMessage = $this->l('Hiển thị từ').' {0} - {1} '.$this->l('trên').' {2}';
		
		$strProfiyLabel = '';
		if ($this->_sTaxEachProduct  == '0')
		{ 	$strProfiyLabel = 'Lãi trước thuế';}
		else
		{ 	$strProfiyLabel = 'Lãi gồm thuế';}
    
		
		$this->_columns = array(
			/* array(
				'id' => 'id_customer',
				'header' => $this->l('Cust ID'),
				'dataIndex' => 'id_customer',
				'align' => 'center',
				'width' => 20
			), */
			array(
				'id' => 'id_order',
				'header' => $this->l('Đơn ID'),
				'dataIndex' => 'id_order',
				'align' => 'center',
				'width' => 20
			),
			array(
				'id' => 'invoice_date',
				'header' => $this->l('Ngày HĐ'),
				'dataIndex' => 'invoice_date',
				'width' => 90,
				'align' => 'center'
			),
			array(
				'id' => 'total',
				'header' => $this->l('Tổng đơn'),
				'dataIndex' => 'total',
				'width' => 90,
				'align' => 'center'
			),
			array(
				'id' => 'RealPaid',
				'header' => $this->l('Thực thu'),
				'dataIndex' => 'RealPaid',
				'width' => 90,
				'align' => 'center'
			),
			array(
				'id' => 'ShipByCust',
				'header' => $this->l('Khách trả Ship'),
				'dataIndex' => 'ShipByCust',
				'width' => 20,
				'align' => 'center'
			),
			array(
				'id' => 'ShipBySeller',
				'header' => $this->l('Sell trả Ship'),
				'dataIndex' => 'ShipBySeller',
				'width' => 20,
				'align' => 'center'
			),
			array(
				'id' => 'TaxTotal',
				'header' => $this->l('Thuế'),
				'dataIndex' => 'TaxTotal',
				'width' => 80,
				'align' => 'center'
			),
			array(
				'id' => 'cost',
				'header' => $this->l('Vốn'),
				'dataIndex' => 'cost',
				'width' => 80,
				'align' => 'left'
			),
			array(
				'id' => 'profit',
				'header' => $strProfiyLabel ,
				'dataIndex' => 'profit',
				'width' => 80,
				'align' => 'left'
			)
			,
			array(
				'id' => 'loss',
				'header' => $this->l('Lỗ'),
				'dataIndex' => 'loss',
				'width' => 80,
				'align' => 'left'
			)
		);
		
		parent::__construct();
		if (!class_exists('nusoap_base')) { require_once ('lib/nusoap.php'); }
		
		$libPath = _PS_MODULE_DIR_.$this->name.'/lib/Classes/';
		if (!class_exists('PHPExcel', false)) {
			set_include_path(get_include_path() . PATH_SEPARATOR . $libPath);
			require_once 'PHPExcel.php';
		}

		$this->displayName = $this->l('N80 - Lãi theo đơn');
		$this->description = $this->l('Danh sách đơn hàng');
		
	}
	
	public function install()
	{
		return (parent::install() AND $this->registerHook('AdminStatsModules'));
	}
	
	public function hookAdminStatsModules($params)
	{
	
		$engineParams = array(
			'id' => 'id_product',
			'title' => $this->displayName,
			'columns' => $this->_columns,
			'defaultSortColumn' => $this->_defaultSortColumn,
			'emptyMessage' => $this->_emptyMessage,
			'pagingMessage' => $this->_pagingMessage
		);
		$currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
	
		//Get list cust id getCust_Id_between
		if (Tools::isSubmit('submitCustId'))
			$this->context->cookie->statsProfit_Cust_Id = Tools::getValue('statsProfit_Cust_Id');
		$sCust_id = ((int)$this->context->cookie->statsProfit_Cust_Id ? $this->context->cookie->statsProfit_Cust_Id : '0');
		$ru = AdminController::$currentIndex.'&module='.$this->name.'&token='.Tools::getValue('token');
		$Customers = Db::getInstance()->ExecuteS($this->getCust_Id_between());
		
		if (Tools::isSubmit('export_excel')) {
				$this->exportExcel();
				exit;
			}
		
		$this->_html = '			
			<div class="panel-heading">'
				.$this->l('Doanh thu theo đơn V3.2').
			'</div>
			<form action="'.$ru.'" method="post" class="form-horizontal">
				<div class="row row-margin-bottom">
					<label class="control-label col-lg-3">'.$this->l('Khách hàng ID: ').'</label>
					<div class="col-lg-6">
						<select name="statsProfit_Cust_Id" onchange="this.form.submit();">
							<option value="0">- '.$this->l('Tất cả').' -</option>';
					foreach ($Customers as $Customer)
						$this->_html .= '<option value="'.(int)$Customer['id_customer'].'" '.
							($sCust_id == $Customer['id_customer'] ? 'selected="selected"' : '').'>'.
							$Customer['id_customer'].'
						</option>';
			$this->_html .= '
						</select>
						<input type="hidden" name="submitCustId" value="1" />
					</div>
				</div>
				<div class="row row-margin-bottom">			
						<div class="col-lg-3">	
							<button name="export_excel" type="submit" class="btn btn-default">
								Xuất ra Excel
							  </button>
						</div>			
						<div class="col-lg-6">				
							
						</div>
					</div>
			</form>';
	
		$this->_html .= '
		<fieldset class="width3"><legend><img src="../modules/'.$this->name.'/logo.gif" /> Chi tiết</legend>
			'.ModuleGrid::engine($engineParams).'
		</fieldset>';
		
		$sEtQuyLo=$this->LayLN('T',$sCust_id);
		if ($sEtQuyLo["V2"] != '')
		{
			$Tong = Db::getInstance()->GetRow($sEtQuyLo["V2"]);
			$FinalProfit = Db::getInstance()->GetRow($sEtQuyLo["V3"]);
		}
		else
		{
			$Tong = Db::getInstance()->GetRow('select 0 as SaleTotal,0 as ShipByCust,0 as ShipBySeller, 0 as TaxTotal,0 as costTotal,0 AS ProfitTotal,0 AS LossTotal');
			$FinalProfit = Db::getInstance()->GetRow('select 0 as ProfitTotal');
		}
		
		$this->_html .= '
		<div class="row row-margin-bottom">
				<p></p>
		</div>
		<div class="panel-heading"><i class="icon-sitemap"></i><strong>'.$this->l(' Tổng hợp').'</strong></div>';
		
		$this->_html .= '
			<table width="1024" class="table">
				<thead>
					<tr>						
						<th width="200"></th>
						<th width="130"></th>
						<th width="90"><strong>'.$this->l('Doanh thu').'</strong></th>
						<th width="80"><strong>'.$this->l('Khách trả ship').'</strong></th>
						<th width="80"><strong>'.$this->l('Sell trả ship').'</strong></th>
						<th width="80"><strong>'.$this->l('Thuế').'</strong></th>
						<th width="80"><strong>'.$this->l('Vốn').'</strong></th>
						<th width="80"><strong>'.$this->l('Lãi').'</strong></th>	
						<th width="80"><strong>'.$this->l('Lỗ').'</strong></th>	
					</tr>
				</thead>
				<tbody>
				</tbody>
				<tfoot>					
					<tr>
						<td>TỔNG</td>
						<td></td>
						<td ><span class="badge">'.$Tong['RealPaid'].'</span></td>
						<td><span class="badge">'.$Tong['ShipByCust'].'</span></td>
						<td><span class="badge">'.$Tong['ShipBySeller'].'</span></td>
						<td><span class="badge">'.$Tong['TaxTotal'].'</span></td>
						<td><span class="badge">'.$Tong['costTotal'].'</span></td>
						<td><span class="badge">'.$Tong['ProfitTotal'].'</span></td>
						<td><span class="badge">'.$Tong['LossTotal'].'</span></td>
					</tr>
					<tr></tr>
					<tr>
						<td><strong>LỢI NHUẬN</strong></td>
						<td><span class="badge">'.$FinalProfit['ProfitTotal'].'</span></td>
						<td ></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
				</tfoot>
			</table>';
		
		$this->_html .= '
		<div class="row row-margin-bottom">
				<p></p>
		</div>
		<div class="panel-heading"><i class="icon-asterisk"></i><strong>'.$this->l(' Note').'</strong></div>';
		
		if ($sEtQuyLo["V2"] != '') 
			$this->_html .= '<p>'.$this->l('Licence: Commercial (Version 3.2 for prestashop 1.6.x). ').'</p>';
		else
			$this->_html .= '<p>'.$this->l('Licence: Only one domain activated per Commercial key (Version 3.2 for prestashop 1.6.x). ').'</p>';
			
	
	/* for($i=1;$i <= 10;$i++)
	{
		$orderInvoice = new OrderInvoice($i);
		$fullNumber = $orderInvoice->getInvoiceNumberFormatted(
					Context::getContext()->language->id
				);					
		$this->_html .='</p>'.' Invoice: '.$fullNumber;		
	}    		 */
	
	//$this->_html .= $_SERVER["REQUEST_URI"] ;
		return $this->_html;
	}
	
	public function getTotalCount($dateBetween,$iCust_Id)
	{
		$strSQL = 'SELECT COUNT(o.`id_order`) as allOrderCount
				FROM `'._DB_PREFIX_.'orders` o			
				WHERE o.valid =1 AND o.total_paid >= 0 AND o.`invoice_date` BETWEEN '.$dateBetween;
				
		if ($iCust_Id != 0)		
		{$strSQL.=' AND o.id_customer = '.$iCust_Id;}
		
		$result = Db::getInstance()->GetRow($strSQL);
		
		return $result['allOrderCount'];
	}

	public function getData()
	{	
		if (Tools::isSubmit('submitCustId'))
			$this->context->cookie->statsProfit_Cust_Id = Tools::getValue('statsProfit_Cust_Id');
		$sCust_id = ((int)$this->context->cookie->statsProfit_Cust_Id ? $this->context->cookie->statsProfit_Cust_Id : '0');
	
		$dateBetween = $this->getDate();
		$this->_totalCount = $this->getTotalCount($dateBetween,$sCust_id);
		$this->_query = '
		SELECT o.id_customer,o.id_order ,o.invoice_date, ROUND( o.total_paid , 2 ) AS total, ROUND((o.total_paid - total_paid_tax_excl - o.total_shipping + o.total_shipping_tax_excl) , 2 ) AS TaxTotal, ROUND( o.total_shipping  , 2 ) AS ShipByCust,0 as ShipBySeller, ((
			SELECT ROUND(SUM(p.wholesale_price * od.product_quantity), 2)
			FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
			LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
			WHERE od.id_order = o.`id_order`
		)) AS cost,
		((
			SELECT ROUND(o.`total_paid`  - SUM(p.wholesale_price * od.product_quantity) , 2)
			FROM '._DB_PREFIX_.'order_detail od
			LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
			LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pa.id_product_attribute = od.product_attribute_id
			WHERE od.id_order = o.`id_order`
		) -		
		(ROUND( o.total_paid_tax_incl - o.total_paid_tax_excl, 2 )) -
		ROUND( o.total_shipping_tax_excl , 2 ) 
		) AS profit, 0 as loss
		FROM `'._DB_PREFIX_.'orders` o		
		WHERE o.valid =1
		AND o.total_paid >= 0 AND o.`invoice_date` BETWEEN '.$dateBetween.'
		GROUP BY o.`id_order`';

		$sLN = $this->LayLN('LN',$sCust_id);	
		if ($sLN != '') $this->_query = $sLN["V2"];	
		
		if (Validate::IsName($this->_sort))
		{
			$this->_query .= ' ORDER BY `'.$this->_sort.'`';
			if (isset($this->_direction))
				$this->_query .= ' '.$this->_direction;
		}
		if (($this->_start === 0 OR Validate::IsUnsignedInt($this->_start)) AND Validate::IsUnsignedInt($this->_limit))
			$this->_query .= ' LIMIT '.$this->_start.', '.($this->_limit);
		$this->_values = Db::getInstance()->ExecuteS($this->_query);
	}



private function LayLN($s)
{
	$sEtQuyLo = null; $sDM = "";
	$dateBetween = $this->getDate();
	$wsdl = "http://localhost/Server/srvSD_HKD_Profit.php?wsdl";
	$client1 = new nusoap_client($wsdl, 'wsdl');
		
	$result1 = $client1->call('wca_GetFirst', array('ProductName'=>$this->_ProductRef,'prefix'=>_DB_PREFIX_));
	$arr = explode(";", $result1); 
		
	$sVal = Db::getInstance()->ExecuteS($arr[0]);	
	$iCount=Db::getInstance()->NumRows();
	
	$sConfig = Configuration::get('PS_SHIPPING_HANDLING').'|'.Configuration::get('PS_SHIPPING_FREE_PRICE').'|'.Configuration::get('PS_SHIPPING_FREE_WEIGHT').'|'._DB_PREFIX_.'|0'.'|'.$this->_sTaxEachProduct ;
	
	if ($iCount > 0)
	{
		If($sVal[0]["value"] != '')
		{
			$sDM = Db::getInstance()->ExecuteS($arr[1]);
			
			if ($s == 'LN')
			{
				$result2 = $client1->call('wca_fcnCP', array('sCMH'=>trim($sVal[0]["value"]),'TM'=>trim($sDM[0]["dm"]),'TenSP'=>$this->_ProductRef,'KhoangNgay'=>$dateBetween,'sConfig'=>$sConfig,'sURL'=>$_SERVER["REQUEST_URI"]));
			}
			else
			{				
				$result2 = $client1->call('wca_fcnTong', array('sCMH'=>trim($sVal[0]["value"]),'TM'=>trim($sDM[0]["dm"]),'TenSP'=>$this->_ProductRef,'KhoangNgay'=>$dateBetween,'sConfig'=>$sConfig,'sURL'=>$_SERVER["REQUEST_URI"]));
			}			
			
			//$sEtQuyLo = $result2["V2"];
			$sEtQuyLo = $result2;
		}
	}
		
	
	return $sEtQuyLo;
}
	public function exportExcel()
	{
		/* if (!class_exists('PHPExcel', false)) {
			$libPath = _PS_MODULE_DIR_.$this->name.'/lib/Classes/';
			set_include_path(get_include_path() . PATH_SEPARATOR . $libPath);
			require_once 'PHPExcel.php';
		} */

		/* ===============================
		   1. LOAD TEMPLATE
		=============================== */

		$templatePath = _PS_MODULE_DIR_.$this->name.'/S2a.xlsx';

		if (!file_exists($templatePath)) {
			die('Không tìm thấy file template S2a.xlsx');
		}

		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objPHPExcel = $objReader->load($templatePath);
		$sheet = $objPHPExcel->getActiveSheet();

		/* =========================
		   1. AHEADER GIỐNG MẪU  
		========================= */
		$dateBetween = $this->getDate();
		$dateBetween_vn = str_replace('AND', 'đến', $dateBetween);
		$sheet->setCellValue('A6', 'Kỳ kê khai từ '.$dateBetween_vn);

		
		
		/* ===============================
		   2. LẤY DỮ LIỆU
		=============================== */
		$dateBetween = $this->getDate();
		$sSQL= $this->GetDoanhThu($dateBetween);
		$data = Db::getInstance()->ExecuteS($sSQL);

		 /* ===============================
		   4. GHI DỮ LIỆU
		=============================== */

		$startRow = 11;   // dòng dữ liệu đầu tiên
		$row = $startRow;
		$currentInvoice = null;
		$startMergeRow = $startRow;

		foreach ($data as $item)
		{
			// Copy style từ dòng mẫu 12 xuống dòng mới
			if ($row > $startRow) {
				$sheet->insertNewRowBefore($row, 1);
				$sheet->duplicateStyle(
					$sheet->getStyle("A{$startRow}:D{$startRow}"),
					"A{$row}:D{$row}"
				);
			}

			// Merge khi đổi invoice
			if ($currentInvoice !== null &&
				$currentInvoice != $item['invoice_num'])
			{
				if ($row - 1 > $startMergeRow) {
					$sheet->mergeCells("A{$startMergeRow}:A".($row-1));
					$sheet->mergeCells("B{$startMergeRow}:B".($row-1));
					$sheet->mergeCells("D{$startMergeRow}:D".($row-1));
				}
				$startMergeRow = $row;
			}
			//Prefix + Invoice
			$orderInvoice = new OrderInvoice($item['invoice_num']);
					$fullInvoice = $orderInvoice->getInvoiceNumberFormatted(
								Context::getContext()->language->id
							);					
				
			$sheet->setCellValue("A{$row}", $fullInvoice);
			$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
			
			$sheet->setCellValue("B{$row}", date('d/m/Y', strtotime($item['invoice_date'])));
			$sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
			
			$sheet->setCellValue("C{$row}", trim($item['name']));
			$sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			
			$sheet->setCellValue("D{$row}", $item['ThucThu']);
			$sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

			$currentInvoice = $item['invoice_num'];
			$row++;
		}

		// Merge invoice cuối
		if ($row - 1 > $startMergeRow) {
			$sheet->mergeCells("A{$startMergeRow}:A".($row-1));
			$sheet->mergeCells("B{$startMergeRow}:B".($row-1));
			$sheet->mergeCells("D{$startMergeRow}:D".($row-1));
		}

		$lastDataRow = $row - 1;

		
		
		/* ===============================
		   Tính thuế GTGT, TNCN
		=============================== */
		$sSQL= $this->MacDinh_CauHinh_SQL();
		$myrows = Db::getInstance()->ExecuteS($sSQL);
		$iMien_Thue = $myrows[0]["Mien_Thue"];
		$sThueXuat_GTGT = (float)$myrows[0]["ThueXuat_GTGT"];
		$sThueXuat_TNCN = (float)$myrows[0]["ThueXuat_TNCN"];
		
		$sSQL= $this->FullSUMSQL();
		$myrows = Db::getInstance()->ExecuteS($sSQL);
		$iTongDT = $myrows[0]["SaleTotal"];
		$iDTChiuThue = $iTongDT  - $iMien_Thue ;
		
		$sheet->setCellValue("C".($lastDataRow + 1), "Tổng doanh thu");
		$sheet->setCellValue("D".($lastDataRow + 1), $iTongDT);
		$sheet->setCellValue("C".($lastDataRow + 2), "Thuế GTGT");
		$sheet->setCellValue("D".($lastDataRow + 2), ($iTongDT * $sThueXuat_GTGT)/100);		
		$sheet->setCellValue("C".($lastDataRow + 3), "Thuế TNCN");
		if ($iDTChiuThue <=0) 
		{ $sheet->setCellValue("D".($lastDataRow + 3), 0 );}
		else
		{ $sheet->setCellValue("D".($lastDataRow + 3), ($iDTChiuThue*$sThueXuat_TNCN)/100 );}
		
		$sheet->setCellValue("D".($lastDataRow + 6), "Ngày " . date("d") . " tháng " . date("m") . " năm " . date("Y") );

		/* ===============================
		   6. FORMAT SỐ
		=============================== */

		$sheet->getStyle("D{$startRow}:D".($lastDataRow+3))
			  ->getNumberFormat()
			  ->setFormatCode('#,##0.00');

		/* ===============================
		   7. EXPORT (QUAN TRỌNG)
		=============================== */

		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="S2a_export.xlsx"');
		header('Cache-Control: max-age=0');

		$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$writer->save('php://output');
		exit;
	}

private function MacDinh_CauHinh_SQL()
		{
			$sEtQuyLo="select  Mien_Thue, ThueXuat_GTGT, ThueXuat_TNCN from tbl_vntaxconfig LIMIT 1"; 
			
			return $sEtQuyLo;
		}
		
		private function GetDoanhThu($dateBetween)
		{
			$sEtQuyLo=' select o.total_paid,(o.total_paid - IFNULL(os.amount, 0)) as ThucThu,oi.number invoice_num,oi.date_add invoice_date, P_List.name, P_List.attribute_name,od.unit_price_tax_incl GiaCoThue, od.product_quantity
						from ps_orders o 
							INNER JOIN ps_order_invoice oi  ON oi.id_order = o.id_order 
							INNER JOIN ps_order_detail od ON o.id_order = od.id_order
							LEFT JOIN ps_order_slip os on o.id_order = os.id_order
							INNER JOIN 
								(
									select p.id_product,pl.name,IF(pa.id_product_attribute is null , 0, pa.id_product_attribute ) as id_product_attribute,
													IF(AName.name is null, "", AName.name) as attribute_name, p.unity,
													ROUND(CASE WHEN pa.price is null THEN p.price  ELSE p.price + pa.price END  , 2 ) AS Price, p.id_tax_rules_group
												From ps_product p
													LEFT JOIN ps_product_attribute pa ON p.id_product = pa.id_product
													LEFT JOIN ps_product_lang pl ON p.id_product = pl.id_product
													LEFT JOIN 
														(select pac.id_product_attribute,al.name
														from  ps_product_attribute_combination pac
															LEFT JOIN ps_attribute_lang al ON pac.id_attribute = al.id_attribute
														WHERE al.id_lang = 1 ) as  AName
													 ON pa.id_product_attribute = AName.id_product_attribute		
												WHERE  pl.id_lang =1
								) P_List ON P_List.id_product = od.product_id and P_List.id_product_attribute = od.product_attribute_id
					WHERE o.valid = 1 AND o.total_paid >= 0 AND o.invoice_date BETWEEN '.$dateBetween;
			
			return $sEtQuyLo;
		}
		
		private function FullSUMSQL()
		{
			$dateBetween = $this->getDate();
			$sSQL= $this->GetDoanhThu($dateBetween);
			
			$strSumSQL='SELECT SUM(t.TongTien) AS SaleTotal
						FROM (
							SELECT tblTotal.invoice_num, MAX(tblTotal.ThucThu) AS TongTien
							FROM ('.$sSQL.') as tblTotal 
							GROUP BY tblTotal.invoice_num
						) t';
			

	
			return $strSumSQL;
		}
			
		
	private function getTotalProducts($dateBetween)
	{
		$result = Db::getInstance()->getRow('
		SELECT SUM(((od.product_price - (CASE WHEN od.reduction_percent = 0 THEN od.reduction_amount ELSE (od.product_price * od.reduction_percent / 100) END)) / o.conversion_rate) * od.product_quantity) - (((((od.product_price - (CASE WHEN od.reduction_percent = 0 THEN od.reduction_amount ELSE (od.product_price * od.reduction_percent / 100) END)) * od.product_quantity) * 100 / o.total_products) * o.total_discounts / 100) / o.conversion_rate) as totalproducts
		FROM '._DB_PREFIX_.'orders o
		LEFT JOIN '._DB_PREFIX_.'order_detail od ON o.id_order = od.id_order
		LEFT JOIN '._DB_PREFIX_.'product p ON od.product_id = p.id_product
		LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON od.product_attribute_id = pa.id_product_attribute
		WHERE o.valid = 1 AND o.total_paid <> 0 AND o.date_add BETWEEN '.$dateBetween);
		return $result['totalproducts'];
		}
		
//Get list of customer Id
 public function getCust_Id_between()
	{
		$dateBetween = $this->getDate();
		
		$strSQL='
		SELECT DISTINCT o.id_customer
		FROM `'._DB_PREFIX_.'orders` o			
		WHERE o.valid =1 AND o.total_paid >= 0 AND o.`invoice_date` BETWEEN '.$dateBetween;
				
		return $strSQL;
	}
		
	
	
}
