<?

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class nodetree {
    public $childs;
    public $btext;
    public $id;
}

class cmstree
{
    //TODO is this used ?? - pedro
    //var $_table;

    // $vars enth�lt:
    // - parent     :id des Elternelementes
    // - type       :n = node, i = item
    // - doctype_id :id des Dokumententyps, wenn nicht im content Feld
    // - title      :Titel des Eintrages
    // - status     :1 = ok, d = delete
    // - icon       :icon im node-tree, optional
    // - modul      :modul des Eintrages, noch nicht verwendet
    // - doc_id     :id des zugeh�rigen Dokumentes

    public function node_list()
    {
        global $app;

	    $nodes = $app->db->queryAllRecords('SELECT * FROM media_cat order by sort, name');

        $optionlist = array();
        $my0 = new nodetree();

        foreach($nodes as $row) {

            $id = 'my'.$row['media_cat_id'];
            $btext = $row['name'];
            $ordner = 'my'.$row['parent'];
            if(!is_object($$id)) $$id = new nodetree();
            $$id->btext = $btext;
            $$id->id = $row['media_cat_id'];

            if(is_object($$ordner)) {
                 $$ordner->childs[] = &$$id;
            } else {
                $$ordner = new nodetree();
                $$ordner->childs[] = &$$id;
            }
        }

        $this->ptree($my0, 0, $optionlist);
        return is_array($nodes) ?  $optionlist : false;
    }

    private function ptree($myobj, $tiefe, &$optionlist){
     	global $_SESSION;
		$tiefe += 1;
		$id = $myobj->id;

        if(is_array($myobj->childs) and ($_SESSION['s']['cat_open'][$id] == 1 or $tiefe <= 1)) {
        	foreach($myobj->childs as $val) {
				// kategorie 		=> str_repeat('- &nbsp;',$tiefe) . $val->btext,

				// Ergebnisse Formatieren
				/*
				if($tiefe == 0) {
					$kategorie = "<div class='mnuLevel".$tiefe."'><a href='index.php?pg=liste&kat=".$val->id."' class='navKategorien'>".$val->btext."</a></div>";
				} elseif ($tiefe == 1) {
					$kategorie = "<div class='mnuLevel".$tiefe."'><img src='images/listenpunkt.gif'> <a href='index.php?pg=liste&kat=".$val->id."' class='navKategorien'>".$val->btext."</a></div>";
				} else {
					$kategorie = "<div class='mnuLevel".$tiefe."'>&nbsp; <a href='index.php?pg=liste&kat=".$val->id."' class='navKategorien'>".str_repeat('- &nbsp;',$tiefe - 1) . $val->btext."</a></div>";
				}
				*/
				$val_id = $val->id;
				if($_SESSION['s']['cat_open'][$val_id] == 1) {
					$kategorie = "<div class='mnuLevel".$tiefe."'>&nbsp; <a href='treenavi.php?kat=".$val->id."' class='navtext' onclick=\"parent.content.location='media_list.php?search_media_cat_id=".$val->id."'\" style=\"text-decoration: none;\"><img src='../themes/default/icons/folder.png' border='0'> ".$val->btext."</a></div>";
				} else {
					$kategorie = "<div class='mnuLevel".$tiefe."'>&nbsp; <a href='treenavi.php?kat=".$val->id."' class='navtext' onclick=\"parent.content.location='media_list.php?search_media_cat_id=".$val->id."'\" style=\"text-decoration: none;\"><img src='../themes/default/icons/folder_closed.png' border='0'> ".$val->btext."</a></div>";
				}

				$optionlist[] = array( 	media_cat 		=> $kategorie,
                                   		media_cat_id 	=> $val->id,
										depth			=> $tiefe);
                $this->ptree($val, $tiefe, $optionlist);
            }
        }
    }

}
?>
