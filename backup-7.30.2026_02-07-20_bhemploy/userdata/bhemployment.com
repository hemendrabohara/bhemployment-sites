--- 
customlog: 
  - 
    format: combined
    target: /etc/apache2/logs/domlogs/bhemployment.com
  - 
    format: "\"%{%s}t %I .\\n%{%s}t %O .\""
    target: /etc/apache2/logs/domlogs/bhemployment.com-bytes_log
documentroot: /home/bhemploy/public_html
group: bhemploy
hascgi: 1
homedir: /home/bhemploy
ip: 136.243.117.33
owner: cheapestprotozoa
phpopenbasedirprotect: 1
port: 80
scriptalias: 
  - 
    path: /home/bhemploy/public_html/cgi-bin
    url: /cgi-bin/
serveradmin: webmaster@bhemployment.com
serveralias: mail.bhemployment.com www.bhemployment.com
servername: bhemployment.com
usecanonicalname: 'Off'
user: bhemploy
