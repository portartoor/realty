<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Anfragen/Anträge");

if (SITE_TEMPLATE_ID == "bitrix24"):
	$html = '<div class="sidebar-buttons"><a href="#SITE_DIR#services/requests/my.php" class="sidebar-button">
			<span class="sidebar-button-top"><span class="corner left"></span><span class="corner right"></span></span>
			<span class="sidebar-button-content"><span class="sidebar-button-content-inner"><i class="sidebar-button-create"></i><b>Meine Anfragen</b></span></span>
			<span class="sidebar-button-bottom"><span class="corner left"></span><span class="corner right"></span></span></a></div>';
	$APPLICATION->AddViewContent("sidebar", $html);
endif?>
<p>Bitte füllen Sie das Ihrer Anfrage entsprechende Formular aus.</p>
<table width="100%" cellspacing="0" cellpadding="3" border="0">
	<tbody>
		<tr><td colspan="6"><b>Bestellungen und Dienstleistungen</b>
		<br />

		<br />
		</td></tr>

		<tr><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=VISITOR_ACCESS"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/card.png" alt="Besucherausweis" title="Besucherausweis" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=VISITOR_ACCESS_#SITE_ID#">Besucherausweis</a></td> 	<td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=COURIER_DELIVERY_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/package.jpg" alt="Versandservic" title="Versandservic" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=COURIER_DELIVERY_#SITE_ID#">Kurierdienst</a></td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=BUSINESS_CARD_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/viscard.png" alt="Visitenkarten" title="Visitenkarten" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=BUSINESS_CARD_#SITE_ID#">Visitenkarten
			<br />
			</a></td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=OFFICE_SUPPLIES_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/kanstov.jpg" alt="Bürobedarf" title="Bürobedarf" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=OFFICE_SUPPLIES_#SITE_ID#">Bürobedarf</a></td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=CONSUMABLES_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/printer.jpg" alt="Verbrauchsmaterial" title="Verbrauchsmaterial" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=CONSUMABLES_#SITE_ID#">Verbrauchsmaterial</a> </td><td align="center">
		<br />
		</td></tr>

		<tr><td colspan="6">
		<br />

		<br />
		</td></tr>

		<tr><td colspan="6"><b>Problemlösungen</b>
		<br />

		<br />
		</td></tr>

		<tr><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=IT_TROUBLESHOOTING_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/computer.jpg" alt="Computer, Netzwerk" title="Computer, Netzwerk" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=IT_TROUBLESHOOTING_#SITE_ID#">Computer, Netzwerk</a> </td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=ADM_TROUBLESHOOTING_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/tool.jpg" alt="Hausmeister" title="Hausmeister" /></a>
		<br />

		<br />
			<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=ADM_TROUBLESHOOTING_#SITE_ID#">Hausmeister</a> </td><td align="center">
		<br />
		</td><td align="center">
		<br />
		</td><td></td><td></td></tr>

		<tr><td colspan="6">
		<br />

		<br />
		</td></tr>

		<tr><td colspan="6"><b>Für die Geschäftsführung</b>
		<br />

		<br />
		</td></tr>

		<tr><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=DRIVER_SERVICES_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/car_driver.jpg" alt="Fahrdienst" title="Fahrdienst" /></a>
		<br />

		<br />
			<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=DRIVER_SERVICES_#SITE_ID#">Fahrdienst</a>
		<br />
		</td><td align="center">
		<p align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=HR_REQUEST_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/person.jpg" alt="Personalgesuch" title="Personalgesuch" /></a>
			<br />

			<br />
			<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=HR_REQUEST_#SITE_ID#">Personalgesuch</a> </p>
		</td><td align="center"><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=WORK_SITE_#SITE_ID#"><img hspace="5" height="70" width="70" vspace="5" border="0" src="#SITE_DIR#images/de/requests/office.jpg" alt="Arbeitsplatzausstattung" title="Arbeitsplatzausstattung" /></a>
		<br />

		<br />
		<a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=WORK_SITE_#SITE_ID#">Arbeitsplatzausstattung</a><a href="#SITE_DIR#services/requests/form.php?WEB_FORM_ID=WORK_SITE_#SITE_ID#"></a></td><td></td><td></td><td></td></tr>
	</tbody>
</table>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>