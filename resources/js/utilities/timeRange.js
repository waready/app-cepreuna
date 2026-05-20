export function timeRange(fecha) {
    let nacimiento = new Date(fecha)
    let hoy = new Date()

    let tiempoPasado= hoy - nacimiento
    let segs = 1000;
    let mins = segs * 60;
    let hours = mins * 60;
    let days = hours * 24;
    let months = days * 30.416666666666668;
    let years = months * 12;

    //calculo
    let anos = Math.floor(tiempoPasado / years);
    if(anos>0){
        return anos+" año(s)";
    }

    tiempoPasado = tiempoPasado - (anos * years);
    let meses = Math.floor(tiempoPasado / months)
    if(meses>0){
        return meses+" mes(es)";
    }

    tiempoPasado = tiempoPasado - (meses * months);
    let dias = Math.floor(tiempoPasado / days)
    if(dias>0){
        return dias +" día(s)";
    }

    tiempoPasado = tiempoPasado - (dias * days);
    let horas = Math.floor(tiempoPasado / hours)
    if(horas>0){
        return horas+" hora(s)";
    }

    tiempoPasado = tiempoPasado - (horas * hours);
    let minutos = Math.floor(tiempoPasado / mins)
    if(minutos>0){
        return minutos+" minuto(s)";
    }

    tiempoPasado = tiempoPasado - (minutos * mins);
    let segundos = Math.floor(tiempoPasado / segs)
    if(segundos>0){
        return segundos+" segundo(s)";
    }
}
