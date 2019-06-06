<?php
/*
**
	FUNCIONES DEL PLUGIN
**
*/

function relacionar_formulario($valor){
	//echo "<h1>Televisor</h1>";
	//relacionar_formulario
	//return $valor;
	global $wpdb;
	global $user;

	//FORMULARIO
	$miFormulario2 = $valor;

	$GLOBALS["formularioSub"] = $miFormulario2;

	global $formularioSub;

	$seImporto = 0;

	//echo "<h1>{$miFormulario} {$wpdb->prefix}</h1>";
	$idUsuario = $user->ID;
	//FORMULARIOS QUE QUEREMOS RELACIONAR 
	$formulariosR = [];
	array_push($formulariosR, $miFormulario);

	//ARREGLO QUE RECORREREMOS SEGUN EL FORMULARIO
	$datosForm = [];

	//AQUI SACAMOS LA CONSULTA PARA TRAERNOS TODOS LOS CAMPOS 
	$parametrosForm = [];

	//EN ESTE VAMOS A TRAER LOS DATOS DEL FORMULARIO
	//SACAMOS EL NOMBRE DEL USUARIO
	$estructuraForm = $wpdb->get_results("SELECT display_meta FROM {$wpdb->prefix}rg_form_meta WHERE form_id = {$miFormulario2} ");

	//AQUI RECORREMOS TODO EL ARREGLO CON LOS ATRIBUTOS DEL FORMULARIO, Y LOS GUARDAMOS PARA PODER UTILIZARLOS MAS ADELANTE.
	foreach ($estructuraForm as $atributoss) {
		//ARREGLO MOMENTANEO
		$momentaneo = [];

		//GUARDAMOS EL ID DEL FORMULARIO Y LOS METAS
		array_push($momentaneo, $miFormulario2);//ID FORMULARIO
		array_push($momentaneo, relacionMetas($atributoss->display_meta));//METAS FORMULARIO

		//GUARDANDO LA DATA EN EL ARREGLO DE FORMULARIO
		array_push($datosForm, $momentaneo);

		//VACIANDO ARREGLO MOMENTANEO
		$momentaneo = [];
	}


	//CONSULTAMOS EL ULTIMO PARAMETRO INSERTADO EN EL FORMULARIO
	for ($i=0; $i < count($datosForm); $i++) { 
		for ($u=0; $u < count($datosForm[$i][1]); $u++) { //REVISAR ESTO PORQUE NO LO ENTENDEMOS
			//REALIZAMOS LA CONSULTA
			$cual = ".";

			$pos = strpos($datosForm[$i][1][$u][0], $cual);
			$busqueda = "";

			if($pos > 0){
				$busqueda = substr($datosForm[$i][1][$u][0], 0, $pos);//ESTO ERA SOLO PARA ELIMINAR EL . (REVISAR)
			}else{
				$busqueda = $datosForm[$i][1][$u][0];
			}

			
		}//AQUI TERMINA EL QUE DEBEMOS REVISAR PORQUE NO LO ENTENDEMOS
		
		//ENCONTRAR ----------------------------> ESTA CONSULTA DEBEMOS MODIFICARLA PARA QUE NO CONSULTE SOLO MI EMAIL SINO TODOS LOS USUARIOS CON INSCRIPCIONES
		//$ultimaInscripcion = $wpdb->get_results("SELECT f.lead_id AS lead_id FROM {$wpdb->prefix}rg_lead_detail AS f WHERE f.form_id = ".$datosForm[$i][0]." AND value = '".$user->user_email."' ORDER BY f.id DESC LIMIT 1");
		$ultimaInscripcion = $wpdb->get_results("SELECT f.lead_id AS lead_id FROM {$wpdb->prefix}rg_lead_detail AS f WHERE f.form_id = ".$datosForm[$i][0]." ");

		//echo "<h1>SELECT f.lead_id AS lead_id FROM {$wpdb->prefix}rg_lead_detail AS f WHERE f.form_id = ".$datosForm[$i][0]." AND value = '".$user->user_email."' ORDER BY f.id DESC LIMIT 1</h1>";

		$idInscripcion = 0;
		$variableAnterior = 0;
		foreach ($ultimaInscripcion as $keys) {
			//echo "jajajjaa";
			if($variableAnterior != $keys->lead_id){
				$idInscripcion .= " ,".$keys->lead_id;
				//echo "<h1>{$keys->lead_id}</h1>";

				$variableAnterior = $keys->lead_id;
			}
		}

		//$datosHijos = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rg_lead_detail AS f WHERE f.form_id = ".$datosForm[$i][0]." AND f.lead_id = ".$idInscripcion." ");
		$datosHijos = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rg_lead_detail AS f WHERE f.form_id = ".$datosForm[$i][0]." AND f.lead_id  IN(".$idInscripcion.") ");

		//echo "<h1>SELECT * FROM {$wpdb->prefix}rg_lead_detail AS f WHERE f.form_id = ".$datosForm[$i][0]." AND f.lead_id  IN(".$idInscripcion.") </h1>";

		$queAtributo = "";
		foreach ($datosHijos as $atributoss) {
			$queAtributo = $atributoss->value;
			$parametroE = $queAtributo;

			//AQUI DEBEMOS LIMPIAR EL ID DEL PARAMETRO 17.6

			$cual = ".";
			$posst = strpos($atributoss->field_number, $cual);
			if($posst > 0){
				$field_numberA = "";
				$field_numberA = substr($atributoss->field_number, 0, $posst);
			}else{
				$field_numberA = $atributoss->field_number;
			}

			$parametroMinuscula1 = str_replace(" ", "_", devuelveLabel($field_numberA));
			$parametroMinuscula = strtolower(sanear_string($parametroMinuscula1));

			//echo "<h1>".devuelveLabel($field_numberA)." {$parametroE}</h1>";

			//VARIABLES
			$labelU = devuelveLabel($field_numberA);
			$valorU = $parametroE;

			echo "<h1>{$labelU}: {$valorU}</h1>";

			//HAGARAREMOS EL EQUIPO PARA PODER 
		}
	}

}

function formulariosWeb(){
	global $wpdb;
	$delimitador = $wpdb->prefix;
	//$delimitador = "wp_2_";
	$cuantosL = explode("_", $delimitador);

	$cuantosL2 = count($cuantosL);


	//ESO DE AQUI ES PORQUE SE UTILIZARA CON UNA RED, ASI QUE LA IDEA ES QUE HAGARRE EL PRIMERO DE LA RED
	if($cuantosL2 >= 3){
		$wpdb->prefix = $cuantosL[0]."_";
	}

	//YA CON NUESTRO PREFIX MODIFICADO PROCEDEMOS A CONSULTAR QUE FORMULARIOS TENEMOS	
	$cualesFormularios = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rg_form WHERE is_active = 1 AND is_trash = 0 ");

	/*
	foreach ($cualesFormularios as $key) {
		ECHO "<h1>{$key->id} {$key->title}</h1>";
	}
	*/


	//echo "<h1>------------------------> ".count($cualesFormularios)." <------------------------------- </h1>";
	//return "este es el formulario {$wpdb->prefix} --> {$cuantosL2}";
	return $cualesFormularios;


}

//AQUI TENEMOS LOS POST

if(isset($_POST['relacionar_formulario']) && $_POST['relacionar_formulario'] != ""){
	//echo "<h1>".relacionar_formulario($_POST['relacionar_formulario'])."</h1>";
	//ENVIAMOS EL ID DEL FORMULARIO A LA FUNCION QUE ME DEVUELVE TODA LA ESTRUCTURA DEL MISMO
	relacionar_formulario($_POST['relacionar_formulario']);
}


?>