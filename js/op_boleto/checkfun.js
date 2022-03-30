//CPF校验
function checkcpf(){
	var cpf = document.getElementById("pay_cpf").value;  
	if(!cpfcheck(cpf)){
		document.getElementById("pay_cpf_pass").value = "0";
	}else{
		document.getElementById("pay_cpf_pass").value = "1";
	}
}

//CNPJ校验
function checkcnpj(){
	var cnpj = document.getElementById("pay_cnpj").value;  
	if(!cnpjcheck(cnpj)){
		document.getElementById("pay_cnpj_pass").value = "0";
	}else{
		document.getElementById("pay_cnpj_pass").value = "1";
	}
}

//切换类型
function changeType(type){
	if(type == 'CPF'){
		document.getElementById("pay_cnpj_div").style.display = "none";
		document.getElementById("pay_cnpj").value = "1";  
		document.getElementById("pay_cnpj_pass").value = "1";
	}else if(type == 'CNPJ'){
		document.getElementById("pay_cnpj_div").style.display = "block";
		document.getElementById("pay_cnpj").value = "";  
		document.getElementById("pay_cnpj_pass").value = "0";
	}
}