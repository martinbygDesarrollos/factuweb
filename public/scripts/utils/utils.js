function getSiteURL(){
	let url = window.location.href;
	if(url.includes("localhost") || url.includes("intranet.gargano"))
		return '/factuweb/public/';
	else
		return '/';
}

function getBase64(file) {
	return new Promise((resolve, reject) => {
		const reader = new FileReader();
		reader.readAsDataURL(file);
		reader.onload = () => resolve(reader.result);
		reader.onerror = error => reject(error);
	});
}

//evento para ocultar y mostrar las opciones del menú
function showMenuClient(menuShow){
	if(menuShow == "CLIENT"){
		$('#listServiceMenu').css('display', 'none');
		$('#listProviderMenu').css('display', 'none');
		$('#listSaleMenu').css('display', 'none');
		if($('#listClientMenu').css('display') == 'block')
			$('#listClientMenu').css('display', 'none');
		else
			$('#listClientMenu').css('display', 'block');
	}else if(menuShow == "PROVIDER"){
		$('#listServiceMenu').css('display', 'none');
		$('#listClientMenu').css('display', 'none');
		$('#listSaleMenu').css('display', 'none');
		if($('#listProviderMenu').css('display') == 'block')
			$('#listProviderMenu').css('display', 'none');
		else
			$('#listProviderMenu').css('display', 'block');
	}else if(menuShow == "SERVICE"){
		$('#listClientMenu').css('display', 'none');
		$('#listProviderMenu').css('display', 'none');
		$('#listSaleMenu').css('display', 'none');
		if($('#listServiceMenu').css('display') == 'block')
			$('#listServiceMenu').css('display', 'none');
		else
			$('#listServiceMenu').css('display', 'block');
	}else if(menuShow == "SALE"){
		$('#listClientMenu').css('display', 'none');
		$('#listProviderMenu').css('display', 'none');
		$('#listServiceMenu').css('display', 'none');
		if($('#listSaleMenu').css('display') == 'block')
			$('#listSaleMenu').css('display', 'none');
		else
			$('#listSaleMenu').css('display', 'block');
	}
}

function getPreviousMonth(isEmited){
	let date = new Date()
	let month = date.getMonth();
	let year = date.getFullYear();

	if ( month == 0 ){
		month = 12;
		year = year-1;
	}

	if(month < 10)
		month = "0" + month ;
	return `${year}-${month}-01`;
}

function getPreviousMonthByDate(currentDate){
	var date = new Date(currentDate); // YYYY-MM-DD

	var d = date.getDate()+1;
	var m = date.getMonth(); // esta funcion comienza a devolver desde el cero
	var y = date.getFullYear();

	if ( m == 0 ){
		m = 12;
		y = y-1;
	}

	var dateString = y + '-' + (m <= 9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d);
	return dateString;
}

function getNextMonth(){
	let date = new Date()
	let month = date.getMonth() + 2;
	let year = date.getFullYear();
	if(month < 10)
		month = "0" + month ;
	return `${year}-${month}-01`;
}


function getLastDayMonth(currentDate){
	let date = new Date(currentDate + 'T00:00');
	date = new Date(date.getFullYear(), date.getMonth()+1, 0);

	let day = date.getDate();
	let month = date.getMonth() + 1;
	let year = date.getFullYear();

	if(month < 10) month = "0" + month ;
	if(day < 10) day = "0" + day;
	return `${year}-${month}-${day}`;
}

function getDateIntToHTML(dateInt){
	dateInt = String(dateInt);
	return dateInt.substr(0,4) + "-" + dateInt.substr(4,2) + "-" + dateInt.substr(6,2);
}

function getFormatDateHTML(currentDate){
	let arrayDate = currentDate.split('/');
	if(arrayDate.length != 3)
		arrayDate = currentDate.split('-');

	return arrayDate[2] + "/" + arrayDate[1] + "/" + arrayDate[0];
}

function onLoadInputDate(inputDate, increaseDates){
	let date = new Date()
	if(increaseDates > 0)
		date.setDate(date.getDate() - increaseDates);
	let day = date.getDate();
	let month = date.getMonth() + 1;
	let year = date.getFullYear();

	if(month < 10) month = "0" + month ;
	if(day < 10) day = "0" + day;
	inputDate.value = `${year}-${month}-${day}`;
}

function getDateEmitted(){
	let date = new Date()

	let day = date.getDate();
	let month = date.getMonth() + 1;
	let year = date.getFullYear();

	if(day > 15){
		day = 1;
		month = month + 1;
	}

	if(month < 10) month = "0" + month ;
	if(day < 10) day = "0" + day;
	return `${year}-${month}-${day}`;
}

function getCurrentDate(){
	var today = new Date();
	var date = null;
	var day = null;
	var month = null;
	var year = null;

	day = today.getDate();
	month = today.getMonth()+1;
	year = today.getFullYear();

	if( day.toString().length == 1 ){
		day = '0'+today.getDate();
	}

	if( month.toString().length == 1 ) {
		month = '0'+(today.getMonth()+1)
	}

	date = year+'-'+month+'-'+day;
	return date;
}

function calculateDateExpiration(currentEmitted, expirationValue){
	let date = new Date(currentEmitted + 'T00:00');

	date.setDate(date.getDate() + parseInt(expirationValue));
	let day = date.getDate();
	let month = date.getMonth() + 1;
	let year = date.getFullYear();

	if(month < 10) month = "0" + month ;
	if(day < 10) day = "0" + day;
	return `${year}-${month}-${day}`;
}

function validateRut(rut){
	var lengthRut = rut.length;
	if (( lengthRut < 10 || lengthRut > 12) || !$.isNumeric(rut)){
		return false;
	}

	if (!/^([0-9])*$/.test(rut)){
		return false;
	}

	var rutDigitVerify = rut.substr((rut.length -1), 1);
	var rutNumber = rut.substr(0, (rut.length -1));

	var total = 0;
	var factors = [2,3,4,5,6,7,8,9,2,3,4];

	j = 0;

	for(i = (rut.length -2); i >= 0; i--){
		total += (factors[j] * rut.substr(i, 1));
		j++;
	}

	var digitVerify = 11 - (total % 11);
	if(digitVerify == 11) digitVerify = 0;
	else if(digitVerify == 10) digitVerify = 1;
	return digitVerify == rutDigitVerify;
}

function validateCI(ci){
	ci = ci.replace(/\D/g, '');

	var dig = ci[ci.length - 1];
	ci = ci.replace(/[0-9]$/, '');
	return (dig == validation_digit(ci));
}

function validation_digit(ci){
	var a = 0;
	var i = 0;
	if(ci.length <= 6){
		for(i = ci.length; i < 7; i++){
			ci = '0' + ci;
		}
	}
	for(i = 0; i < 7; i++){
		a += (parseInt("2987634"[i]) * parseInt(ci[i])) % 10;
	}
	if(a%10 === 0){
		return 0;
	}else{
		return 10 - a % 10;
	}
}

function calculeQuote (currentValue, quote, currentMoney, moneyToConvert){
	console.log("calculeQuote" + currentValue + " - " + quote + " - " + currentMoney + " - " + moneyToConvert)
	var newValue = 1;


	if (currentMoney == 'UYU'){
		if (moneyToConvert == 'USD'){
			newValue = parseFloat(currentValue) / parseFloat(quote);
		}
	}
	else if (currentMoney == 'USD'){
		if (moneyToConvert == 'UYU'){
			newValue = parseFloat(currentValue) * parseFloat(quote);
		}
	}

	newValue = parseFloat(newValue).toFixed(2);
	console.log(newValue)
	return newValue;
}


function parceDateForInput(dateToChange){
	let dateArray = dateToChange.split('/');
	return `${dateArray[2]}-${dateArray[1]}-${dateArray[0]}`;
}

function parceDateFormatBar(dateToChange){
	let dateArray = dateToChange.split('-');
	return `${dateArray[2]}/${dateArray[1]}/${dateArray[0]}`;
}

function getIvaValue(idIva){
	let value = 1;
	switch (idIva) {
		case 2:
			value = 1.1;
			break;
		case 3:
			value = 1.22;
			break;
			
		default:
			value = 1;
			break;
	}
	return value;
}