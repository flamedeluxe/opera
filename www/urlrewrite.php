<?
$arUrlRewrite = array(
	/* Театр: контакты и услуги — свой шаблон uuopera (демо-каталог отключён). */
	array(
		"CONDITION"	=>	"#^/services(/.*)?$#",
		"RULE"	=>	"",
		"ID"	=>	"",
		"PATH"	=>	"/services/index.php",
	),
	array(
		"CONDITION"	=>	"#^/contacts(/.*)?$#",
		"RULE"	=>	"",
		"ID"	=>	"",
		"PATH"	=>	"/contacts/index.php",
	),
	array(
		"CONDITION"	=>	"#^/products/#",
		"RULE"	=>	"",
		"ID"	=>	"bitrix:catalog",
		"PATH"	=>	"/products/index.php",
	),
	array(
		"CONDITION"	=>	"#^/news/#",
		"RULE"	=>	"",
		"ID"	=>	"bitrix:news",
		"PATH"	=>	"/news/index.php",
	),
);

?>