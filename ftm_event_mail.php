<?php
/**
 * @package ftm_event_mail 
 * @author Ralf Meyer
 * @link einsatzkomponente.de
 * &licence GNU/GPL 
 *
 */
defined('_JEXEC') or die('Restricted access');


jimport('joomla.event.plugin');
jimport( 'joomla.registry.registry' );

class plgSystemFtm_event_mail extends JPlugin {
	
	function onAfterInitialise() { 
	
	
		$query = 'SELECT * FROM #__firefighters_termine WHERE state = "1" AND datum_start > Current_TimeStamp AND datum_start < DATE_ADD(Current_TimeStamp, INTERVAL '.$this->params->get('hours','24').' HOUR) AND email = "1" AND email_gesendet = "0" ORDER BY datum_start ASC' ;
		$db	= JFactory::getDBO();
		$db->setQuery( $query );
		$termine = $db->loadObjectList(); 
		
		$debug = $this->params->get('debug','0');
		
//print_r ($termine);

if (count($termine) > 0) {
	foreach ($termine AS $termin) {

					if (isset($termin->abteilungen)) {
						$values = explode(',', $termin->abteilungen);

						$email_adressen	= '';
						$emailValue = array();
						$textValue = array();
						foreach ($values as $value){
							$db = JFactory::getDbo();
							$query = $db->getQuery(true);
							$query
									->select('*')
									->from('`#__firefighters_abteilungen`')
									->where('id = ' . $db->quote($db->escape($value)));
							$db->setQuery($query);
							$results = $db->loadObject();
							
								if ($results) {
									$textValue[] = $results->name; 
									
										$db = JFactory::getDbo();
										$query = $db->getQuery(true);
										$query
												->select('emailadresse')
												->from('`#__firefighters`')
												->where('state = 1 AND FIND_IN_SET(' . $results->id. ',abteilungen)');
										$db->setQuery($query);
										$email_results = $db->loadObjectList();
										
												foreach ($email_results AS $emails) {
														if ($emails) {
															$emailValue[] = $emails->emailadresse; 
														}
												}
									
									
								}
						}

					$termin->abteilungen = !empty($textValue) ? implode(', ', $textValue) : $termin->abteilungen;
					//$email_adressen = !empty($emailValue) ? implode(', ', array_unique ($emailValue)) : $email_adressen;
					}

//echo $termin->datum_start.' für '.$termin->abteilungen.'  Debug:'.$debug;
//echo $email_adressen;
	
	
		
			$bug_email = array();
			$send_email = array();
			$mailliste       = array_unique($emailValue);
			foreach ($mailliste AS $empf) {
			$mail = JFactory::getMailer();
			$toemail       = $empf; 
			if ($debug) : $toemail  =$this->params->get('admin'); endif;
			$subject       = 'Termin :'.$termin->name.' (für '.$termin->abteilungen.')';
			//$mail->addAttachment($attachment);
			$mail->addRecipient($toemail);
				//$mail->addBCC(""); 
				$mail->setSubject($subject);
				$mail->isHTML(true);
				$mail->setBody('<h1>Termin: '.$termin->name.' <small>(für '.$termin->abteilungen.')</small></h1>Datum: '.$termin->datum_start.' Uhr</br>'.$termin->beschreibung);
					if(!$mail->Send()) {
						if ($this->params->get('admin')) {
						$mail = JFactory::getMailer();
						$mail->addRecipient($this->params->get('admin'));
						$mail->setSubject('Nicht versendet an '.$toemail.':'.$subject);
						$mail->isHTML(true);
						$mail->setBody('<h1>Termin: '.$termin->name.' <small>(für '.$termin->abteilungen.')</small></h1>Datum: '.$termin->datum_start.' Uhr</br>'.$termin->beschreibung);
						$mail->Send();
						$bug_email [] = $toemail;
						}
					}
					else {
						$send_email [] = $toemail;
						}
			}
			
				$send_email = implode(', ', $send_email);
				//echo 'versendet: '.$send_email;
				if (!$debug) {  
				$db		= JFactory::getDBO();
				$query	= $db->getQuery(true);
				$query->update('#__firefighters_termine');
				$query->set('email_gesendet = "1" ');
				$query->where('`id` ="'.$termin->id.'"');
				$db->setQuery((string) $query);

				try
				{
					$db->execute();
				}
				catch (RuntimeException $e)
				{
					JError::raiseError(500, $e->getMessage());
				}
				}
				
	}

}

		
		}
		
	 
	
}
?>