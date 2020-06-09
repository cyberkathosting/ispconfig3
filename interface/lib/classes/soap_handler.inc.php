<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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


class ISPConfigSoapHandler extends ISPConfigRemotingHandlerBase {
	public function __call($method, $params) {
		if(array_key_exists($method, $this->methods) == false) {
			throw new SoapFault('invalid_method', 'Method ' . $method . ' does not exist');
		}

		$class_name = $this->methods[$method];
		if(array_key_exists($class_name, $this->classes) == false) {
			throw new SoapFault('invalid_class', 'Class ' . $class_name . ' does not exist');
		}

		if(method_exists($this->classes[$class_name], $method) == false) {
			throw new SoapFault('invalid_method', 'Method ' . $method . ' does not exist in the class it was expected (' . $class_name . ')');
		}

		try {
			return call_user_func_array(array($this->classes[$class_name], $method), $params);
		} catch(SoapFault $e) {
			throw $e;
		}
	}

}

?>
