<?php
session_start();
include("../inc_dbconnect.php");
include("../inc_checkerror.php");
include ("../quotation_sales_data_new.php");

include("invoice_db.php");
$invoice = new invoiceDB($dbname);

header('Content-Type: text/html; charset=TIS-620');


if(isset($_GET[qid]) && !empty($_GET[qid])){
    $query = $invoice->invoiceQuery("SELECT * FROM ready_office_quotation WHERE qid = '$_GET[qid]'");
    
    $checkHas = mysql_num_rows($query);
    if($checkHas == 1){
        $row = mysql_fetch_array($query);
        
        $isSP = ($row[special] == 'Yes') ? 2 : 1;
        $clearingRow = $invoice->getClearing($_GET[qid]);
        $saleRow = $invoice->getSale($row[creator_admin_id]);
    }else{
        exit();
    }
    
}

?>
<head>
    <title>Quotation</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
    <style>
        td,div,p{font:11px Microsoft Sans Serif,Tahoma;color:#000000;}
        h1{display:inline;font:35px Arial,Verdana;color:#000000;font-weight:bold;}
        h2{display:inline;font:13px Microsoft Sans Serif,Tahoma;color:#000000;font-weight:bold;}
        h3{display:inline;font:20px Microsoft Sans Serif,Tahoma;color:#000000;font-weight:bold;}
        td.border{border:1px solid #000000;}
    </style>
</head>
<body bgcolor="#CCCCCC">
    <table width="780" border="0" cellspacing="10" cellpadding="0" bgcolor="#FFFFFF">
        <tr>
            <td colspan="2" height="8"></td>
        </tr>
        
        <tr>
            <td width="48%"><img src="../../images/ReadyPlanet-logo-2013.png" width="40%" /></td>
            <td width="52%">&nbsp;</td></tr>
        <tr>
            <td>
                <h2><?=$row[creator_company]?></h2><br />
                <?=$row[creator_address]?><br />
                <?= "Tel : " . $tel_us . ", &nbsp;Fax : " . $fax_us . " " ?><br />
                Email : <a href="mailto:info@readyplanet.com" target="_blank">info@readyplanet.com</a><br />
                Tax ID : 0105543071964 <br />
            </td>
            <td align="right" valign='top' style="padding-right:15px;">
                <h1>Quotation</h1><br />
                <h3>No. QA<?=$row[qno]?></h3>
            </td>
        </tr>

        <tr valign="top">
            <td style="padding-left:10px;">
                <table width="100%" border="0" cellspacing="5" cellpadding="0">
                    <tr>
                        <td width="20%" valign="top" nowrap><b>Attention : </b></td>
                        <td valign="top" nowrap><?=$row[attention]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Company : </b></td>
                        <td valign="top"><?=$row[company]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Address : </b></td>
                        <td valign="top"><?=$row[addr]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Province : </b></td>
                        <td valign="top"><?=$row[province]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Zipcode : </b></td>
                        <td valign="top"><?=$row[zip]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Tel : </b></td>
                        <td valign="top"><?=$row[tel]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Fax : </b></td>
                        <td valign="top"><?=$row[fax]?></td>
                    </tr>
                    
                </table>
            </td>
            <td>
                <table width="100%" border="0" cellspacing="5" cellpadding="0">
                    <tr>
                        <td width="25%" valign="top"><b>From : </b></td>
                        <td valign="top"><?=$row[creator]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Tel : </b></td>
                        <td valign="top"><?=$tel_office . ' Ext ' . $row[creator_int_number]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Direct line : </b></td>
                        <td valign="top"><?=$row[direct_line]?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Fax : </b></td>
                        <td valign="top"><?=$fax_us?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top"><b>Create Date : </b></td>
                        <td valign="top"><?=$invoice->convertDate($row[issue])?></td>
                    </tr>
                    
                    <tr>
                        <td valign="top" nowrap><b>Valid Until : </b></td>
                        <td valign="top"><?=$invoice->convertDate($row[validdate])?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="2" style="padding-left:10px;padding-bottom:5px;"><B>We are pleased to submit you the following service quotation : </b></td>
        </tr>
       
            <tr>
                <td colspan="2" valign="top">

                    <table width="96%" cellpadding="2" cellspacing="0" border="0" bordercolor="#000000" style="border-collapse:collapse;" align="center">
                        <tr align="center">
                            <td class="border" style="border-left:0px;" width="1%" align='center'><h6>Item</h6></td>
                            <td class="border" width="10%" align='center'><h6>Product Code</h6></td>
                            <td class="border" width="81%" align='center'><h6>Service</h6></td>
                            <td class="border" width="10%" align='center'><h6>*BOI / NB </h6></td>
                            <td class="border" width="11%" align='center'><h6>Price / Unit</h6></td>
                            <td class="border" colspan="2" width="8%" align='center'><h6>Unit(s)</h6></td>
                            <td class="border" style="border-right:0px;" width="1%" align='center'>
                                <h6>Total<br/>(<?=$row[monetary_type]?>)</h6>
                            </td>
                        </tr>

                        <?php
                        for($index = 1, $itemcount = 1;$index < 10;$index++){
                            if($row["productid$index"] != ''){
                                $productRow = $invoice->getProduct($row["productid$index"]);
                                
                                if($productRow[pname] == 'Facebook Advertising Cost'){$checkFAC == TRUE;}
                                
                                ?>

                                <tr align="center" valign="top">
                                    <td><?=$itemcount?></td>
                                    <td align="center" style="padding-left:5px;"><?=$productRow[pro_acode]?></td>
                                    <td align="left" style="padding-left:5px;"><?php 
                                    if($isSP == 1 || empty($row["service$index"])){
                                            echo nl2br($productRow[pname])."<br/>";
                                    }?><?=$row["service$index"]?>
                                    </td>
                                    <td align="center"><?php if($productRow[BOI] == 'B'){echo "BOI";$countBOI++;}else{echo "Non-BOI";}?></td>
                                    <td align="right" style="padding-right:10px;"><?=number_format($row["price$index"],2,'.',',')?></td>
                                    <td align="center" colspan="2"><?=$row["unit$index"]?></td>
                                    <td align="right"><?=number_format($row["amount$index"],2,'.',',')?></td>
                                </tr>
                        <?php
                                $itemcount++;
                                
                                //-----------------cal withholding part1
                                if($clearingRow[CustomerType] == "official" && $productRow[BOI] == "NB"){
                                    $tax = $productRow[officialTax];
                                    $sumOF += $row["amount$index"];
                                    
                                }else if($clearingRow[CustomerType] == "corporation" && $productRow[BOI] == "NB"){
                                    $tax = $productRow[corporationTax];
            
                                    if($tax == 2){
                                        $taxAd = $tax;
                                        $sumAdCO += $row["amount$index"];
                                    }else{
                                        $taxCO = $tax;
                                        $sumCO += $row["amount$index"];
                                    }
                                }
                            }
                        }
                        
                        //if ($row[note_webpro]) { ?>			
                            <tr align="center" valign="top">
                                <td></td>
                                <td colspan='2' align="left" cellpadding="0" cellspacing="10">
                                    <?=nl2br($row[note_webpro])?>
                                    <br /><br /><br /><br />
                                </td>
                            </tr>
                        <?php //} ?>
	
                        <tr align="center">
                            <td colspan="3" rowspan="3" align="left">
                                <?php
                                if ($clearingRow[withholding_tax] > 0) {
                                    $minWithholding = 1000;
                                    echo "<br> �ó��ѡ���� � ������";
                                    
                                    if($clearingRow[CustomerType] == "official" && $sumOF > $minWithholding){
                                        $result = ($sumOF * $tax) / 100;
                                        echo "<br> * �����ѡ � ������ $tax% �ͧ��Һ�ԡ�� ".number_format($sumOF,2,'.',',')." �ҷ = ".number_format($result,2,'.',',')." �ҷ";
                                    }else if($clearingRow[CustomerType] == "corporation" && ($sumAdCO > $minWithholding || $sumCO > $minWithholding)){
                                        if($sumAdCO > $minWithholding){
                                            $resultAd = ($sumAdCO * $taxAd) / 100;
                                            echo "<br> * �����ѡ � ������ $taxAd% �ͧ����ɳ� ".number_format($sumAdCO,2,'.',',')." �ҷ = ".number_format($resultAd,2,'.',',')." �ҷ";
                                        }else{
                                            $resultAd = 0;
                                        }
        
                                        if($sumCO > $minWithholding){
                                            $result = ($sumCO * $taxCO) / 100;
                                            echo "<br> * �����ѡ � ������ $taxCO% �ͧ��Һ�ԡ�� ".number_format($sumCO,2,'.',',')." �ҷ = ".number_format($result,2,'.',',')." �ҷ";
                                        }else{
                                            $result = 0;
                                        }
        
                                        $sum = $result + $resultAd;
                                    }
                                    
                                    echo "<br> * �ʹ���� ".number_format($row[nettotalprice], 2, '.', ',')." - ".number_format($clearingRow[withholding_tax],2,'.',',')." = ".number_format($clearingRow[netAmount],2,'.',',')." �ҷ";
                                }
                                
                                if ($countBOI > 0) {
                                    echo "<br> * �Թ��� \"BOI\" �١�������ͧ�ѡ���� � ������";
                                }
                                ?>
                            </td>
                            <td colspan="3" style="border-bottom:1px dotted #000000;" align="left"><b>Sub Total</b></td>
                            <td colspan="2" style="border-bottom:1px dotted #000000;"  align="right">
                                <?=number_format($row[totalprice], 2, '.', ',')?>
                            </td>
                        </tr>
                        
                        <tr align="center">
                            
                            <td colspan="3" style="border-bottom:1px solid #000000;" align="left"><b>Vat (<?=$row[percentVat]?>%)</b></td>
                            <td colspan="2" style="border-bottom:1px solid #000000;"  align="right">
                                <?php if($row[vatprice] == 0){echo '-';}else{echo number_format($row[vatprice],2,'.',',');}?>
                            </td>
                        </tr>
                        
                        <tr align="center">
                            
                            <td colspan="3" style="border-bottom:3px double #000000;" align="left"><b>Grand Total</b></td>
                            <td colspan="2" style="border-bottom:3px double #000000;" align="right">
                                <?=number_format($row[nettotalprice], 2, '.', ',')?>
                            </td>
                        </tr>
                        
                        <tr align="left"><td colspan='8' style='border-bottom:2px solid #000000;'></td></tr>
                        
                        <tr>
                            <td colspan='8'>
                                <?php
                                if ($row[withholdingTax] > 0 || $checkFAC) {
                                    echo "<br>�����˵�";
                                }
                                
                                
                                if ($clearingRow[withholding_tax] > 0) {
                                    echo "<br> - 㹡óշ���١������ѡ���� � �����������ѹ�����Թ ��س���˹ѧ����Ѻ�ͧ����ѡ � �����¨ӹǹ 2 ��Ѻ���ѧ ����ѷ� ���� 7 �ѹ<br />����ͷҧ����ѷ� ���Ѻ�͡��äú��ǹ���� �ШѴ�� \"㺡ӡѺ����\" �����١��ҷҧ��ɳ���ѹ��";
                                }
                                
                                
                                if ($checkFAC) {
                                    echo "<br> - ���͹�����ͧ�������ҡ������ԡ�� facebook : ������ҳ�������������ö���ԡ��������Թ 1 �� �Ѻ�ҡ�ѹ����ɳ�������ʴ���˹�� facebook ";
                                }
                                ?>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>

            <?php
            if ($saleRow[position_id] == '7' || $admin_name == "Areesa" || $admin_name == "Chatchawan") {
                echo "<tr valign='top'>";
                echo "<td colspan='6'><h2>***</h2><u>���͹�����ͧ�������ҡ������ԡ�õ��ᾤࡨ</u> :
                    �������Һ�ԡ���ɳ����䫵� ������Ѻ������ɳҢ���ʴ����������ѹ�á ���֧�������Ҥú���ᾤࡨ �ó���ش�ɳҪ��Ǥ���㹪�ǧ���Ңͧᾤࡨ���� 
                    ��ʧǹ�Է��㹡������͹�������Һ�ԡ�� �ҤҤ�Һ����èѴ��ù�� ����Ѻ�١��ҷ����ǧ�Թ��Ҥ�ԡ�ɳ�����Թ 50,000 �ҷ�����͹��ҹ��</td>";
                echo "</tr>";
            }
            ?>

            <tr valign="top">
                <td colspan="2">
                    
                    <?php if($isSP == 1){ ?>

                    <h2><u>��ê����Թ</u></h2>
                    <br />
                    <br />
                    �͹�Թ��ҹ��Ҥ����Һѭ�ժ���&quot;����ѷ �ô����Ź�� �ӡѴ&quot;
                    <br />
                    <table width="60%" cellpadding="0" cellpadding="3" border="0">
                        <tr>
                            <td>��Ҥ�á�ԡ���</td>
                            <td>�ҢҶ��������˧ 151</td>	
                            <td>�ѭ�������Ѿ��</td>
                            <td>�Ţ��� 735-2-29271-5</td>
                        </tr>
                        <tr>
                            <td>��Ҥ���¾ҳԪ�� </td>
                            <td>�ҢҶ��������˧ (����ҡ�)</td>
                            <td>�ѭ�������Ѿ��</td>
                            <td>�Ţ��� 136-2-12270-6</td>
                        </tr>
                        <tr>
                            <td>��Ҥ�á�ا෾</td>
                            <td>�ҢҶ��������˧ (�آ��Ժ�� 3)</td>
                            <td>�ѭ�������Ѿ��</td>
                            <td>�Ţ��� 056-0-18526-6</td>
                        </tr>
                        <tr>
                            <td>��Ҥ�á�ا��</td>
                            <td>�Ң��آ��Ժ�� 2</td>
                            <td>�ѭ�������Ѿ��</td>
                            <td>�Ţ��� 197-1-10016-1</td>
                        </tr>
                        <tr>
                            <td>��Ҥ�á�ا�����ظ��</td>
                            <td>�Ң��آ��Ժ�� 2</td>
                            <td>�ѭ�������Ѿ��</td>
                            <td>�Ţ��� 296-1-19001-6</td>
                        </tr>
                        <tr>
                            <td>��Ҥ�ø��ҵ</td>
                            <td>�Ң��Ѫ��-���¢�ҧ</td>
                            <td>�ѭ�ա��������ѹ</td>
                            <td>�Ţ��� 295-3-00826-4</td>
                        </tr>
                    </table>
                    <?= "(��س�����ѡ�ҹ����͹�Թ�ҧ Fax " . $fax_us . " ���͡�õ�Ǩ������׹�ѹ)" ?>
                    <?php                     
                    }else{ ?>
                        <h2><u>Payment Method</u></h2>
                        <br />
                        <br />
                        Please make a money transfer to "Readyplanet Co.,Ltd" account as follows :
                        <br />
                        <table width="60%" cellpadding="0" cellpadding="3" border="0">
                            <tr>
                                <td>Siam Commercial Bank</td>
                                <td>Ramkhamhaeng 151 Road Branch</td>	
                                <td>Saving account</td>
                                <td># 136-2-12270-6</td>
                            </tr>
                            
                            <tr>
                                <td>Krungthai Bank</td>
                                <td>Sukhaphiban 3 Branch</td>
                                <td>Saving account</td>
                                <td># 197-1-10016-1</td>
                            </tr>
                        </table>
                   <?php }?>

                </td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                                                
                    <br /><br /><br /><br /><br />

                    <table width="90%">
                        <tr height="1" valign="bottom">
                            <td width="55%">
                                <span style="font-size:13px;"><b>Approved by</b> ______________________________</span>
                            </td>
                            
                            <td>
                                <span style="font-size:13px;">
                                    <b>Proposed by </b> 
                                        <?php if ($saleRow[SaleID] != 99) { 
                                            print "<img src='../../images/signature"; 
                                            print $saleRow[SaleID]; 
                                            print ".gif' hspace='15'>"; 
                                            
                                        } else { 
                                            print " ____________________________"; 
                                            
                                        } ?>
                                </span>
                            </td>
                        </tr>
                        
                        <tr>
                            <td><span style="margin:0 0 0 100;">(  </span><span style='margin:0 0 0 180;'> )</span></td>
                            <td><div style="margin:0 0 0 105;"> ( <?=$row[creator]?> ) </div></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" height="60">&nbsp;</td>
            </tr>
    </table>
</body>