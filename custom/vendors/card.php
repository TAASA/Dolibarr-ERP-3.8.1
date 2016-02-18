<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
$candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
    $caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
    $caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
    || (($user->id != $id) && $user->rights->user->user->password));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2='user';
if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
if (!$canreaduser) {
	$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");
$langs->load("admin");

$object = new User($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard','globalcard'));

$form = new Form($db);
$formother=new FormOther($db);
$fuserstatic = new User($db);



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// $parameters=array();
// $reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
// if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// if (empty($reshook))
// {
//  // Action to add record
//  if ($action == 'add')
//  {
//      if (GETPOST('cancel'))
//      {
//          $urltogo=$backtopage?$backtopage:dol_buildpath('/mymodule/list.php',1);
//          header("Location: ".$urltogo);
//          exit;
//      }

//      $error=0;

//      /* object_prop_getpost_prop */
//      $object->prop1=GETPOST("field1");
//      $object->prop2=GETPOST("field2");

//      if (empty($object->ref))
//      {
//          $error++;
//          setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")),'errors');
//      }

//      if (! $error)
//      {
//          $result=$object->create($user);
//          if ($result > 0)
//          {
//              // Creation OK
//              $urltogo=$backtopage?$backtopage:dol_buildpath('/mymodule/list.php',1);
//              header("Location: ".$urltogo);
//              exit;
//          }
//          {
//              // Creation KO
//              if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
//              else  setEventMessages($object->error, null, 'errors');
//              $action='create';
//          }
//      }
//      else
//      {
//          $action='create';
//      }
//  }

//  // Cancel
//  if ($action == 'update' && GETPOST('cancel')) $action='view';

//  // Action to update record
//  if ($action == 'update' && ! GETPOST('cancel'))
//  {
//      $error=0;

//      $object->prop1=GETPOST("field1");
//      $object->prop2=GETPOST("field2");

//      if (empty($object->ref))
//      {
//          $error++;
//          setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")),null,'errors');
//      }

//      if (! $error)
//      {
//          $result=$object->update($user);
//          if ($result > 0)
//          {
//              $action='view';
//          }
//          else
//          {
//              // Creation KO
//              if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
//              else setEventMessages($object->error, null, 'errors');
//              $action='edit';
//          }
//      }
//      else
//      {
//          $action='edit';
//      }
//  }

//  // Action to delete
//  if ($action == 'confirm_delete')
//  {
//      $result=$object->delete($user);
//      if ($result > 0)
//      {
//          // Delete OK
//          setEventMessages("RecordDeleted", null, 'mesgs');
//          header("Location: ".dol_buildpath('/buildingmanagement/list.php',1));
//          exit;
//      }
//      else
//      {
//          if (! empty($object->errors)) setEventMessages(null,$object->errors,'errors');
//          else setEventMessages($object->error,null,'errors');
//      }
//  }
// }




/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Vendedores','');


if($id > 0)
{
    $object->fetch($id);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals($object->id,$extralabels);
 if ($action != 'edit')
        {
            dol_fiche_head($head, 'user', $title, 0, 'user');

            $rowspan=19;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
            print '<td colspan="3">';
            print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
            print '</td>';
            print '</tr>'."\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
            if (! empty($conf->societe->enabled)) $rowspan++;
            if (! empty($conf->adherent->enabled)) $rowspan++;
            if (! empty($conf->skype->enabled)) $rowspan++;
            if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read)) $rowspan = $rowspan+3;
            if (! empty($conf->agenda->enabled)) $rowspan++;

            // Lastname
            print '<tr><td>'.$langs->trans("Lastname").'</td>';
            print '<td colspan="2">'.$object->lastname.'</td>';

            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $form->showphoto('userphoto',$object,100);
            print '</td>';

            print '</tr>'."\n";

            // Firstname
            print '<tr><td>'.$langs->trans("Firstname").'</td>';
            print '<td colspan="2">'.$object->firstname.'</td>';
            print '</tr>'."\n";

            // Position/Job
            print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
            print '<td colspan="2">'.$object->job.'</td>';
            print '</tr>'."\n";

      //       // Gender
            // print '<tr><td>'.$langs->trans("Gender").'</td>';
            // print '<td>';
            // if ($object->gender) print $langs->trans("Gender".$object->gender);
            // print '</td></tr>';

      //       // Login
      //       print '<tr><td>'.$langs->trans("Login").'</td>';
      //       if (! empty($object->ldap_sid) && $object->statut==0)
      //       {
      //           print '<td colspan="2" class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
      //       }
      //       else
      //       {
      //           print '<td colspan="2">'.$object->login.'</td>';
      //       }
      //       print '</tr>'."\n";

      //       // Password
      //       print '<tr><td>'.$langs->trans("Password").'</td>';
      //       if (! empty($object->ldap_sid))
      //       {
      //           if ($passDoNotExpire)
      //           {
      //               print '<td colspan="2">'.$langs->trans("LdapUacf_".$statutUACF).'</td>';
      //           }
      //           else if($userChangePassNextLogon)
      //           {
      //               print '<td colspan="2" class="warning">'.$langs->trans("UserMustChangePassNextLogon",$ldap->domainFQDN).'</td>';
      //           }
      //           else if($userDisabled)
      //           {
      //               print '<td colspan="2" class="warning">'.$langs->trans("LdapUacf_".$statutUACF,$ldap->domainFQDN).'</td>';
      //           }
      //           else
      //           {
      //               print '<td colspan="2">'.$langs->trans("DomainPassword").'</td>';
      //           }
      //       }
      //       else
      //       {
      //           print '<td colspan="2">';
      //           if ($object->pass) print preg_replace('/./i','*',$object->pass);
      //           else
      //           {
      //               if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
      //               else print $langs->trans("Hidden");
      //           }
      //           print "</td>";
      //       }
      //       print '</tr>'."\n";

      //       // API key
      //       if(! empty($conf->api->enabled) && $user->admin) {
      //           print '<tr><td>'.$langs->trans("ApiKey").'</td>';
      //           print '<td colspan="2">';
      //           if (! empty($object->api_key))
      //               print $langs->trans("Hidden");
      //           print '<td>';
      //       }

            // Administrator
   //          print '<tr><td>'.$langs->trans("Administrator").'</td><td colspan="2">';
   //          if (! empty($conf->multicompany->enabled) && $object->admin && ! $object->entity)
   //          {
   //              print $form->textwithpicto(yn($object->admin),$langs->trans("SuperAdministratorDesc"),1,"superadmin");
   //          }
   //          else if ($object->admin)
   //          {
   //              print $form->textwithpicto(yn($object->admin),$langs->trans("AdministratorDesc"),1,"admin");
   //          }
   //          else
   //          {
   //              print yn($object->admin);
   //          }
   //          print '</td></tr>'."\n";

   //          // Type
   //          print '<tr><td>';
   //          $text=$langs->trans("Type");
   //          print $form->textwithpicto($text, $langs->trans("InternalExternalDesc"));
   //          print '</td><td colspan="2">';
   //          $type=$langs->trans("Internal");
   //          if ($object->societe_id > 0) $type=$langs->trans("External");
            // print $type;
   //          if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
   //          print '</td></tr>'."\n";

   //          // Ldap sid
   //          if ($object->ldap_sid)
   //          {
   //           print '<tr><td>'.$langs->trans("Type").'</td><td colspan="2">';
   //           print $langs->trans("DomainUser",$ldap->domainFQDN);
   //           print '</td></tr>'."\n";
   //          }

   //          // Tel pro
   //          print '<tr><td>'.$langs->trans("PhonePro").'</td>';
   //          print '<td colspan="2">'.dol_print_phone($object->office_phone,'',0,0,1).'</td>';
   //          print '</tr>'."\n";

   //          // Tel mobile
   //          print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
   //          print '<td colspan="2">'.dol_print_phone($object->user_mobile,'',0,0,1).'</td>';
   //          print '</tr>'."\n";

   //          // Fax
   //          print '<tr><td>'.$langs->trans("Fax").'</td>';
   //          print '<td colspan="2">'.dol_print_phone($object->office_fax,'',0,0,1).'</td>';
   //          print '</tr>'."\n";

   //          // Skype
   //          if (! empty($conf->skype->enabled))
   //          {
            //  print '<tr><td>'.$langs->trans("Skype").'</td>';
   //              print '<td colspan="2">'.dol_print_skype($object->skype,0,0,1).'</td>';
   //              print "</tr>\n";
   //          }

   //          // EMail
   //          print '<tr><td>'.$langs->trans("EMail").'</td>';
   //          print '<td colspan="2">'.dol_print_email($object->email,0,0,1).'</td>';
   //          print "</tr>\n";

   //          // Signature
   //          print '<tr><td class="tdtop">'.$langs->trans('Signature').'</td><td colspan="2">';
   //          print dol_htmlentitiesbr($object->signature);
   //          print "</td></tr>\n";

   //          // Hierarchy
   //          print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
   //          print '<td colspan="2">';
   //          if (empty($object->fk_user)) print $langs->trans("None");
   //          else {
   //           $huser=new User($db);
   //           $huser->fetch($object->fk_user);
   //           print $huser->getNomUrl(1);
   //          }
   //          print '</td>';
   //          print "</tr>\n";

       //      if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
       //      {
       //       $langs->load("salaries");

          //       // THM
                // print '<tr><td>';
                // $text=$langs->trans("THM");
                // print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
                // print '</td>';
                // print '<td colspan="2">';
                // print ($object->thm!=''?price($object->thm,'',$langs,1,-1,-1,$conf->currency):'');
                // print '</td>';
                // print "</tr>\n";

          //       // TJM
                // print '<tr><td>';
                // $text=$langs->trans("TJM");
                // print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
                // print '</td>';
                // print '<td colspan="2">';
                // print ($object->tjm!=''?price($object->tjm,'',$langs,1,-1,-1,$conf->currency):'');
                // print '</td>';
                // print "</tr>\n";

                // // Salary
                // print '<tr><td>'.$langs->trans("Salary").'</td>';
                // print '<td colspan="2">';
                // print ($object->salary!=''?price($object->salary,'',$langs,1,-1,-1,$conf->currency):'');
                // print '</td>';
                // print "</tr>\n";
       //      }

         //    // Weeklyhours
         //    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
         //    print '<td colspan="2">';
            // print price2num($object->weeklyhours);
         //    print '</td>';
         //    print "</tr>\n";

            // // Accountancy code
            // if ($conf->salaries->enabled)
            // {
            //  print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
            //  print '<td colspan="2">'.$object->accountancy_code.'</td>';
            // }

            // // Color user
            // if (! empty($conf->agenda->enabled))
   //          {
            //  print '<tr><td>'.$langs->trans("ColorUser").'</td>';
            //  print '<td colspan="2">';
            //  print $formother->showColor($object->color, '');
            //  print '</td>';
            //  print "</tr>\n";
            // }

            // // Status
            // print '<tr><td>'.$langs->trans("Status").'</td>';
            // print '<td colspan="2">';
            // print $object->getLibStatut(4);
            // print '</td>';
            // print '</tr>'."\n";

            // print '<tr><td>'.$langs->trans("LastConnexion").'</td>';
            // print '<td colspan="2">'.dol_print_date($object->datelastlogin,"dayhour").'</td>';
            // print "</tr>\n";

            // print '<tr><td>'.$langs->trans("PreviousConnexion").'</td>';
            // print '<td colspan="2">'.dol_print_date($object->datepreviouslogin,"dayhour").'</td>';
            // print "</tr>\n";

            // if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
            // {
            //     print '<tr><td>'.$langs->trans("OpenIDURL").'</td>';
            //     print '<td colspan="2">'.$object->openid.'</td>';
            //     print "</tr>\n";
            // }

            // // Company / Contact
            // if (! empty($conf->societe->enabled))
            // {
            //     print '<tr><td>'.$langs->trans("LinkToCompanyContact").'</td>';
            //     print '<td colspan="2">';
            //     if (isset($object->societe_id) && $object->societe_id > 0)
            //     {
            //         $societe = new Societe($db);
            //         $societe->fetch($object->societe_id);
            //         print $societe->getNomUrl(1,'');
            //     }
            //     else
            //     {
            //         print $langs->trans("ThisUserIsNot");
            //     }
            //     if (! empty($object->contact_id))
            //     {
            //         $contact = new Contact($db);
            //         $contact->fetch($object->contact_id);
            //         if ($object->societe_id > 0) print ' / ';
            //         else print '<br>';
            //         print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
            //     }
            //     print '</td>';
            //     print '</tr>'."\n";
            // }

            // // Module Adherent
            // if (! empty($conf->adherent->enabled))
            // {
            //     $langs->load("members");
            //     print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
            //     print '<td colspan="2">';
            //     if ($object->fk_member)
            //     {
            //         $adh=new Adherent($db);
            //         $adh->fetch($object->fk_member);
            //         $adh->ref=$adh->getFullname($langs); // Force to show login instead of id
            //         print $adh->getNomUrl(1);
            //     }
            //     else
            //     {
            //         print $langs->trans("UserNotLinkedToMember");
            //     }
            //     print '</td>';
            //     print '</tr>'."\n";
            // }

            // Multicompany
            // TODO This should be done with hook formObjectOption
            if (is_object($mc))
            {
                if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                {
                    print '<tr><td>'.$langs->trans("Entity").'</td><td width="75%" class="valeur">';
                    if (empty($object->entity))
                    {
                        print $langs->trans("AllEntities");
                    }
                    else
                    {
                        $mc->getInfo($object->entity);
                        print $mc->label;
                    }
                    print "</td></tr>\n";
                }
            }

            // Other attributes
            $parameters=array('colspan' => ' colspan="2"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
                print $object->showOptionals($extrafields);
            }

            print "</table>\n";

            dol_fiche_end();


            /*
             * Buttons actions
             */

    //         print '<div class="tabsAction">';

    //         if ($caneditfield && (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
    //         {
    //             if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
    //             {
    //                 print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("DisabledInMonoUserMode")).'">'.$langs->trans("Modify").'</a></div>';
    //             }
    //             else
    //             {
    //                 print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
    //             }
    //         }
    //         elseif ($caneditpassword && ! $object->ldap_sid &&
    //         (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
    //         {
    //             print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("EditPassword").'</a></div>';
    //         }

    //         // Si on a un gestionnaire de generation de mot de passe actif
    //         if ($conf->global->USER_PASSWORD_GENERATED != 'none')
    //         {
                // if ($object->statut == 0)
                // {
       //              print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("ReinitPassword").'</a></div>';
                // }
    //             elseif (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
    //             ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
    //             {
    //                 print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a></div>';
    //             }

                // if ($object->statut == 0)
                // {
       //              print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("SendNewPassword").'</a></div>';
                // }
    //             else if (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
    //             ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
    //             {
    //                 if ($object->email) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a></div>';
    //                 else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendNewPassword").'</a></div>';
    //             }
    //         }

            // Activer
            // if ($user->id <> $id && $candisableuser && $object->statut == 0 &&
            // ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            // {
            //     print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a></div>';
            // }
            // // Desactiver
            // if ($user->id <> $id && $candisableuser && $object->statut == 1 &&
            // ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            // {
            //     print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=disable&amp;id='.$object->id.'">'.$langs->trans("DisableUser").'</a></div>';
            // }
            // // Delete
            // if ($user->id <> $id && $candisableuser &&
            // ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            // {
            //  if ($user->admin || ! $object->admin) // If user edited is admin, delete is possible on for an admin
            //  {
            //      print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteUser").'</a></div>';
            //  }
            //  else
            //  {
            //      print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("MustBeAdminToDeleteOtherAdmin")).'">'.$langs->trans("DeleteUser").'</a></div>';
            //  }
            // }

            // print "</div>\n";
            // print "<br>\n";



            /*
             * Liste des groupes dans lequel est l'utilisateur
             */

            // if ($canreadgroup)
            // {
            //     print_fiche_titre($langs->trans("ListOfGroupsForUser"),'','');

            //     // On selectionne les groupes auquel fait parti le user
            //     $exclude = array();

            //     $usergroup=new UserGroup($db);
            //     $groupslist = $usergroup->listGroupsForUser($object->id);

            //     if (! empty($groupslist))
            //     {
            //         if (! (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)))
            //         {
            //             foreach($groupslist as $groupforuser)
            //             {
            //                 $exclude[]=$groupforuser->id;
            //             }
            //         }
            //     }

            //     if ($caneditgroup)
            //     {
            //         print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
            //         print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
            //         print '<input type="hidden" name="action" value="addgroup" />';
            //         print '<table class="noborder" width="100%">'."\n";
            //         print '<tr class="liste_titre"><th class="liste_titre" width="25%">'.$langs->trans("GroupsToAdd").'</th>'."\n";
            //         print '<th>';
            //         print $form->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity);
            //         print ' &nbsp; ';
            //         // Multicompany
            //         if (! empty($conf->multicompany->enabled))
            //         {
            //             if ($conf->entity == 1 && $conf->multicompany->transverse_mode)
            //             {
            //                 print '</td><td>'.$langs->trans("Entity").'</td>';
            //                 print "<td>".$mc->select_entities($conf->entity);
            //             }
            //             else
            //             {
            //                 print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
            //             }
            //         }
            //         else
            //         {
            //          print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
            //         }
            //         print '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
            //         print '</th></tr>'."\n";
            //         print '</table></form>'."\n";

            //         print '<br>';
            //     }

            //     /*
            //      * Groups assigned to user
            //      */
            //     print '<table class="noborder" width="100%">';
            //     print '<tr class="liste_titre">';
            //     print '<td class="liste_titre" width="25%">'.$langs->trans("Groups").'</td>';
            //     if(! empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            //     {
            //      print '<td class="liste_titre" width="25%">'.$langs->trans("Entity").'</td>';
            //     }
            //     print "<td>&nbsp;</td></tr>\n";

            //     if (! empty($groupslist))
            //     {
            //         $var=true;

            //         foreach($groupslist as $group)
            //         {
            //             $var=!$var;

            //             print "<tr ".$bc[$var].">";
            //             print '<td>';
            //             if ($caneditgroup)
            //             {
            //                 print '<a href="'.DOL_URL_ROOT.'/user/group/card.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
            //             }
            //             else
            //             {
            //                 print img_object($langs->trans("ShowGroup"),"group").' '.$group->name;
            //             }
            //             print '</td>';
            //             if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            //             {
            //              print '<td class="valeur">';
            //              if (! empty($group->usergroup_entity))
            //              {
            //                  $nb=0;
            //                  foreach($group->usergroup_entity as $group_entity)
            //                  {
            //                      $mc->getInfo($group_entity);
            //                      print ($nb > 0 ? ', ' : '').$mc->label;
            //                      print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'&amp;entity='.$group_entity.'">';
            //                      print img_delete($langs->trans("RemoveFromGroup"));
            //                      print '</a>';
            //                      $nb++;
            //                  }
            //              }
            //             }
            //             print '<td align="right">';
            //             if ($caneditgroup && empty($conf->multicompany->transverse_mode))
            //             {
            //                 print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'">';
            //                 print img_delete($langs->trans("RemoveFromGroup"));
            //                 print '</a>';
            //             }
            //             else
            //             {
            //                 print "&nbsp;";
            //             }
            //             print "</td></tr>\n";
            //         }
            //     }
            //     else
            //     {
            //         print '<tr '.$bc[false].'><td colspan="3">'.$langs->trans("None").'</td></tr>';
            //     }

            //     print "</table>";
            //     print "<br>";
            // }
        }   
}
 


// End of page
llxFooter();
$db->close();
