itsmi
=====

##Checklist
+ Slim PhP Middleware
+ curl tests

##Logic

###Registration
+ client sends e-mail address
+ if unknown address: server sends confirmation e-mail with one time one hour valid token
+ client sends e-mail address + confirmation token
+ server validates e-mail + token request (and inserts new user) and sends json answer (success or failure) and sends e-mail with token
+ if known address: server sends e-mail with explanation, registers attempt time to prevent multiple e-mails

###Login
+ client sends e-mail address and token and login time (rsa) as one time login token
+ server decodes one time login token
+ if token is valid, server creates JWT
+ server sends json answer (success or failure)
