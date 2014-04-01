/*
Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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



This Javascript is invoked by
	* dns/templates/dns_dkim_edit.htm to get the public key 
*/
        var request = false;

        function setRequest(zone) {
                if (window.XMLHttpRequest) {request = new XMLHttpRequest();}
                else if (window.ActiveXObject) {
                        try {request = new ActiveXObject('Msxml2.XMLHTTP');}
                        catch (e) {
                                try {request = new ActiveXObject('Microsoft.XMLHTTP');}
                                catch (e) {}
                        }
                }
                if (!request) {
                        alert("Error creating XMLHTTP-instance");
                        return false;
                } else {
                        request.open('POST', 'dns/dns_dkim_get.php', true);
                        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        request.send('&zone='+zone);
                        request.onreadystatechange = interpretRequest;
                }
        }

        function interpretRequest() {
                switch (request.readyState) {
                        case 4:
                                if (request.status != 200) {alert("Request done but NOK\nError:"+request.status);}
                                else {
                                        document.getElementsByName('data')[0].value = request.responseXML.getElementsByTagName('data')[0].firstChild.nodeValue;
                                        document.getElementsByName('name')[0].value = request.responseXML.getElementsByTagName('name')[0].firstChild.nodeValue;
										document.getElementsByName('selector')[0].value = request.responseXML.getElementsByTagName('selector')[0].firstChild.nodeValue;
                                }
                                break;
                        default:
                                break;
                }
        }

var serverType = jQuery('#zone').val();
setRequest(serverType);
