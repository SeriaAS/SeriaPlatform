<s:gui title="{'This template uses translated texts'|_t}">
	<h1 class='legend'>{{"This template uses translated texts"|_t}}</h1>
	<p>{{"Testing mappings: %0%, %1%, %2%"|_t('%0%'|_t("A"), "B", "C")}}</p>
	<p>{{"Testing \"string parsing\"..."}} {{'\'Second\''}}, {{'"third"'}}, {{"'Fourth'"}} and {{'Fifth "..\' ..'}}</p>
</s:gui>