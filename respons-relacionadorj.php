<?php
   /*
   Plugin Name: respons relacionadorj
   Plugin URI: http://www.keylimetec.com/
   description: Primer Plugin de Keylimetec
   Version: 1.3
   Author: Joser
   Author URI: http://www.keylimetec.com/
   License: GPL2
   */


/** Step 2 (from text above). */
add_action( 'admin_menu', 'menu_g_form_user_destination' );

/** Step 1. */
function menu_g_form_user_destination() {
	//add_options_page( 'Gf user Destination', 'GF usuarios', 'manage_options', 'my-unique-identifier', 'my_plugin_options' );
	add_options_page( 'Gf user Destination', 'Relacionador', 'manage_options', 'my-unique-identifier', 'my_plugin_options' );
	//ESTE DE ABAJO ES PARA CREAR UN MENU PRINCIPAL
	//add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
}

/** Step 3. */
function my_plugin_options() {
	global $wpdb;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

?>
<div class="wrap">
	<h1>Relacionar jugadores con equipos:</h1>

	<br />
	<?php
		//SI LE DAMOS CLICK AL BOTON DE RELACIONAR
		if(isset($_POST['relacionar'])){
			//echo "<h1>Relacionando</h1>";
			//PRIMERO DEBEMOS CONSULTAR LOS EQUIPOS
			$equipos = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'sp_team' ");

			foreach ($equipos as $key) {
				//echo "<h1>{$key->ID} {$key->post_title}</h1>";
				$competiciones = [];
				$season = [];
				$jugadoresArray = [];

				//CONSULTAMOS TODAS LAS COMPETICIONES DE ESTE EQUIPO
				//$conmp = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = {$key->ID} AND meta_key = 'sp_list' ");
				$conmp = $wpdb->get_results("SELECT r.term_taxonomy_id FROM {$wpdb->prefix}term_relationships AS r WHERE r.object_id = {$key->ID} AND (SELECT tx.taxonomy FROM {$wpdb->prefix}term_taxonomy AS tx WHERE tx.term_id =  r.term_taxonomy_id) = 'sp_league' ");

				foreach ($conmp as $competicion) {
					//echo "<h2>{$competicion->term_taxonomy_id}</h2>";

					//GUARDAMOS LAS COMPETICIONES
					array_push($competiciones, $competicion->term_taxonomy_id);
				}
				//sp_season
				$conmp = $wpdb->get_results("SELECT r.term_taxonomy_id FROM {$wpdb->prefix}term_relationships AS r WHERE r.object_id = {$key->ID} AND (SELECT tx.taxonomy FROM {$wpdb->prefix}term_taxonomy AS tx WHERE tx.term_id =  r.term_taxonomy_id) = 'sp_season' ");

				foreach ($conmp as $competicion) {
					//echo "<h2>{$competicion->term_taxonomy_id}</h2>";
					//GUARDAMOS LAS COMPETICIONES
					array_push($season, $competicion->term_taxonomy_id);
				}

				//CONSULTAMOS LOS JUGADORES DE ESTE EQUIPO
				$jugador = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}posts AS p WHERE p.ID IN(SELECT m.post_id FROM {$wpdb->prefix}postmeta AS m WHERE m.meta_value = {$key->ID} AND m.meta_key = 'sp_team') AND p.post_type = 'sp_player' ");

				foreach ($jugador as $jkey) {
					//GUARDAMOS EL JUGADOR EN EL ARREGLO
					array_push($jugadoresArray, $jkey->ID);

					//echo "<span>{$jkey->ID} {$jkey->post_title}</span><br />";

					//CONSULTAMOS SI ESTE JUGADOR ESTA EN ALGUNA DE LAS COMPETICIONES EN DONDE ESTA EL EQUIPOS
					for ($i=0; $i < count($competiciones); $i++) { 

						//echo "<h1>Competicion -> {$competiciones[$i]}</h1>";
						//AQUI RELACIONAMOS
						$activo = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}term_relationships WHERE object_id = {$jkey->ID} AND term_taxonomy_id = {$competiciones[$i]} ");

						if($activo <= 0){//SI NO EXISTE ESTA TAXONOMIA LA CREAMOS
							$wpdb->insert( 
								$wpdb->prefix."term_relationships", 
								array( 
									'object_id' 		=> $jkey->ID,
									'term_taxonomy_id' 	=> $competiciones[$i],
									'term_order' 		=> '0'
								) 
							);

							//echo "relacionando";
						}else{
							//echo "<br> Existe {$jkey->ID} {$competiciones[$i]}<br>";
						}
					}

					for ($i=0; $i < count($season); $i++) { 
						//echo "<h1>Competicion -> {$competiciones[$i]}</h1>";
						//AQUI RELACIONAMOS
						$activo = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}term_relationships WHERE object_id = {$jkey->ID} AND term_taxonomy_id = {$season[$i]} ");
						if($activo <= 0){//SI NO EXISTE ESTA TAXONOMIA LA CREAMOS
							$wpdb->insert( 
								$wpdb->prefix."term_relationships", 
								array( 
									'object_id' 		=> $jkey->ID,
									'term_taxonomy_id' 	=> $season[$i],
									'term_order' 		=> '0'
								) 
							);
							//echo "relacionando";
						}else{
							//echo "<br> Existe {$jkey->ID} {$competiciones[$i]}<br>";
						}
					}
				}

				//BACIAMOS EL ARREGLO DE COMPETICIONES
				$competiciones = [];
				$season = [];
				$jugadoresArray = [];
			}

			//AQUI RELACIONAREMOS EQUIPOS CON JUGADORES
			//RELACIONAMOS LOS JUGADORES QUE ESTAN EN ESTE EQUIPO CON LOS PARTIDOS
			//UNA VES RELACIONADA LA TEMPORADA Y EL SEASON DEBEMOS RELACIONAR LOS JUEGOS
			$equipos = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'sp_team' ");

			foreach ($equipos as $key) {
				//ESTO ES SI EXISTE UNA CANTIDAD DE JUEGOS EN LOS QUE ESTA ESTE EQUIPO
				//if ($jugara > 0) {
					//echo "<h1> SELECT COUNT(*) FROM {$wpdb->prefix}postmeta AS m WHERE m.post_id IN(SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'sp_event' ) AND m.meta_key = 'sp_team' AND m.meta_value = {$key->ID}  </h1>";
					$partidos = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta AS m WHERE m.post_id IN(SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'sp_event' ) AND m.meta_key = 'sp_team' AND m.meta_value = {$key->ID}");
					//echo "<h1>SELECT * FROM {$wpdb->prefix}postmeta AS m WHERE m.post_id IN(SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'sp_event' ) AND m.meta_key = 'sp_team' AND m.meta_value = {$key->ID}</h1>";
					//AQUI YA TENGO EL POST ID QUE NECESITAMOS
					//DEBEMOS SACAR LOS DOS EQUIPOS QUE ESTARAN EN ESTE PARTIDO Y AGREGAR LOS JUGADORES
					foreach ($partidos as $qPartidos) {
						// /$post_id//ESTE ES EL EVENTO
						//echo "<h3>{$qPartidos->post_id}</h3>";
						$cEquipo = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}postmeta AS p WHERE p.post_id = {$qPartidos->post_id} AND p.meta_key = 'sp_team' ");
						//BORRAMOS TODOS LOS JUGADORES
						foreach ($cEquipo as $idEquipo) {
							$wpdb->delete( $wpdb->prefix.'postmeta', array( 'post_id' => $idEquipo->post_id, 'meta_key' => 'sp_player' ) );
						}

						foreach ($cEquipo as $idEquipo) {
							//echo "<h1>{$idEquipo->post_id}</h1>";
							//echo "<h1>{$idEquipo->meta_value}</h1>";//ESTO IMPRIME EL ID DEL ARTICULO
							$queInsertar = $wpdb->insert( 
													$wpdb->prefix."postmeta", 
													array( 
														'post_id' 		=> $idEquipo->post_id,
														'meta_key' 		=> 'sp_player',
														'meta_value' 	=> 0
													) 
									);
							$idDelquipo = $idEquipo->meta_value;
							//CONSULTAMOS LOS JUGADORES QUE ESTEN RELACIONADOS CON ESTE EQUIPO 
							$rJugador = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}postmeta AS p WHERE p.meta_key = 'sp_team' AND p.meta_value = {$idDelquipo}");
							foreach ($rJugador as $uJugador) {
								//DESGLOSAMOS EL JUGADOR DEL EQUIPO
								$cJugador = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}posts AS p WHERE p.ID = {$uJugador->post_id} AND p.post_type = 'sp_player' ");
								//echo "SELECT p.* FROM {$wpdb->prefix}posts AS p WHERE p.ID = {$uJugador->post_id} AND p.post_type = 'sp_player'  <br>";
								foreach ($cJugador as $keyJ) {
									//echo "{$keyJ->post_title}<br>";//ESTO IMPRIME EL JUGADOR POR EQUIPO
									$insertamos = $wpdb->insert( 
													$wpdb->prefix."postmeta", 
													array( 
														'post_id' 		=> $idEquipo->post_id,
														'meta_key' 		=> 'sp_player',
														'meta_value' 	=> $keyJ->ID
													) 
									);
								}
							}
						}
					}
				//}
			}

			//------------------> RELACION FINAL CON LOS EQUIPOS Y TODO LO DEMAS
			$taxonomiasA = [];
			$taxonomiasAA = [];

			$cuantasLigas = 0;

			$idJugador = 0;

			//HACEMOS CONSULTAS DEMO
			//$jugador = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'sp_player' AND p.ID = '79' ");
			$jugador = $wpdb->get_results("SELECT p.* FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'sp_player' ");

			foreach ($jugador as $key) {
				$cuantasLigas = 0;
				$cuantosAnos = 0;
				//CAMBIAMOS EL ID DEL JUGADOR
				$idJugador = $key->ID;
				//EN CADA JUGADOR CONSULTAREMOS SU EQUIPO ACTUAL
				$aEquipo = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE  post_id = '{$key->ID}' AND meta_key = 'sp_current_team' ");

				//EN CADA JUGADOR CONSULTAREMOS SU EQUIPO PASADO
				$pEquipo = $wpdb->get_results("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE  post_id = '{$key->ID}' AND meta_key = 'sp_past_team' ");

				//YA CON LOS DATOS DE LOS EQUIPOS NUEVOS Y PASADOS CONSULTAREMOS LAS RELACIONES POR EQUIPO  wp_term_relationships
				foreach ($aEquipo as $aEqui) {
					$momentaneo = [];
					$anoL = [];
					$ligaL = [];

					//echo "<h1>{$aEqui->meta_value}</h1>";
					//AQUI CONSULTAMOS LAS TAXONOMIAS
					$rAequipo = $wpdb->get_results("SELECT term_taxonomy_id FROM {$wpdb->prefix}term_relationships WHERE  object_id = '{$aEqui->meta_value}'  ");

					foreach ($rAequipo as $taxo) {
						//echo "<h1>Taxonomias padre: {$taxo->term_taxonomy_id}</h1>";
						$queEs = $wpdb->get_results("SELECT taxonomy FROM {$wpdb->prefix}term_taxonomy WHERE  term_id = '{$taxo->term_taxonomy_id}'  ");
						$queTaxonomi = "";
						foreach ($queEs as $keyss) {
							//echo "<h1>{$keyss->taxonomy}</h1>";
							$queTaxonomi = $keyss->taxonomy;
						}

						//DEBERIAMOS DIVIDIRLO POR EL AÑO:
						if($queTaxonomi == "sp_season"){
							//echo $queTaxonomi."<br>";
							//GUARDAMOS LA TAXONOMIA DE LOS EQUI
							$anoL[] = $taxo->term_taxonomy_id;
							$cuantosAnos++;
						}
						if($queTaxonomi == "sp_league"){
							//echo $queTaxonomi."<br>";
							//GUARDAMOS LA TAXONOMIA DE LOS EQUI
							$ligaL[] = $taxo->term_taxonomy_id;
							$cuantasLigas++;
						}
					}

					$momentaneo[] = $aEqui->meta_value;//GUARDAMOS EL ID DEL EQUIPO
					$momentaneo[] = $anoL;//GUARDAMOS LAS COMPETICIONES DEL EQUIPO
					$momentaneo[] = $ligaL;//GUARDAMOS LAS COMPETICIONES DEL EQUIPO

					//GUARDAMOS TODO EN LA TAXONOMIA
					$taxonomiasA[] = $momentaneo;

					//

					$taxonomiasAA[] = $momentaneo;

					$momentaneo = [];
					$anoL = [];
					$ligaL = [];
				}

				//SACAMOS LA CANTIDAD DE RELACIONES QUE TIENE ESTE JUGADOR EN PARTICULAR
				//sp_current_team
				//$cantidadR = count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_relationships WHERE object_id = {$key->ID} "));
				
				
				$insertar = "a:";
				$insertar .= $cuantasLigas + 1;
					$insertar .= ":{";
						

				//ARREGLAREMOS POR A;OS
				for ($i=0; $i < count($taxonomiasAA); $i++) { 
					//echo "<h1>".$taxonomiasA[$i][0]." ".count($taxonomiasA[$i][1])."</h1>";
					for ($w=0; $w < count($taxonomiasA[$i][2]); $w++) { //ESTE ES LIGAS
						$insertar .= "i:".$taxonomiasA[$i][2][$w].";";
						$insertar .= "a:".count($taxonomiasA[$i][1]).":{";

						/* RADA DE ESTO SIRVE YA LO LOGRAMOS EN LAS LINEAS DE ABAJO
						$tablas = count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = '".$taxonomiasA[$i][0]."' AND meta_key = 'sp_table' "));
						$listas = count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = '".$taxonomiasA[$i][0]."' AND meta_key = 'sp_list' "));
						$cantidadR = $tablas + $listas;

						//RELACIONES
						$relaciones = count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_relationships WHERE object_id = '".$taxonomiasA[$i][0]."' "));

						$cantidadR = $cantidadR + $relaciones;

						$relaciones = count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_relationships WHERE object_id = '".$taxonomiasA[$i][0]."' "));

						//
						$cantidadR = count($wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_relationships WHERE object_id = '".$idJugador."' "));
						*/
						

						for ($z=0; $z < count($taxonomiasA[$i][1]); $z++) { 
							$cantidadR = strlen($taxonomiasA[$i][0]);
							$insertar .= 'i:'.$taxonomiasA[$i][1][$z].';s:'.$cantidadR.':"'.$taxonomiasA[$i][0].'";';
						}

						$insertar .= "}";
					}					
				}

				//AQUI AGREGAMOS EL RESTO DE LIGAS 
				$arregloFinal = [];
				//AGREGAMOS LA QUE ES EN 0
				for ($i=0; $i < count($taxonomiasAA); $i++) {
					for ($w=0; $w < count($taxonomiasA[$i][2]); $w++) { //ESTE ES LIGAS
						//$insertar .= "i:0;";
						//$insertar .= "a:".$cuantasLigas.":{";

						for ($z=0; $z < count($taxonomiasA[$i][1]); $z++) { 
							if(!in_array($taxonomiasA[$i][1][$z], $arregloFinal)){
								//echo "<h1>( {$taxonomiasA[$i][1][$z]} )</h1>";
								array_push($arregloFinal, $taxonomiasA[$i][1][$z]);
							}
							
							//$insertar .= 'i:'.$taxonomiasA[$i][1][$z].';s:1:"1";';
						}

						// /$arregloFinal2 = array_unique($arregloFinal);
						//$insertar .= "}";
					}
					//LUEGO DE AGREGAR LA FINAL 
					//$insertar .= "i:0;a:1:";
					//$insertar .= '{i:9;s:1:"1";}';
				}

				$insertar .= "i:0;a:";
				$insertar .= count($arregloFinal);
					$insertar .= ":{";

				for ($z=0; $z < count($arregloFinal); $z++) { 
					//echo "<h1>{$arregloFinal[$z]}</h1>";
					$insertar .= 'i:'.$arregloFinal[$z].';s:1:"1";';
				}

					$insertar .= "}";
				$insertar .= "}";

				//echo $insertar."<br>";//-------------------------->AQUI NOS QUEDAMOS

				//AHACEMOS EL UPDATE
				$wpdb->update( 
	                  $wpdb->prefix."postmeta", 
	                  array( 
	                    'meta_value' => $insertar 
	                  ), 
	                  array( 
	                    'post_id' => $idJugador,
	                    'meta_key' => 'sp_leagues'
	                    ) 
	                );

				if($idJugador == 7105){
					echo "<h1>{$insertar}</h1>";
				}else{
					//echo "<h1>{$idJugador}</h1>";
				}

				$insertar = "";

				//AL FINALIZAR TODO BASIAMOS LAS TAXONOMIAS PARA PODER SER UTILIZADAS POR OTRO JUGADOR
				$taxonomiasA = [];

				$arregloFinal = [];

				$taxonomiasAA = [];
			}

			echo "<h1>Todo se edito 22</h1>";
		}

		echo "<h1>{$wpdb->prefix}</h1>";
	?>

	<form method="post" action="">
    	<input type="hidden" name="relacionar" />
	    <?php submit_button('Relacionar'); ?>
	</form>

</div>
<?php
}