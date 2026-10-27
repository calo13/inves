<?php
it('redirige usuarios sin sesión',function(){$this->get('/')->assertRedirect('/ingresar');});
