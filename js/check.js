/**
 * Oletetaan, että meillä on päällä rakenne <div class="col-sm.."></div>, joka
 * sisältää joukon yhteen lomakkeen kontrolliin liittyviä asioita. Itse kontrollia seuraa
 * spani, jossa on ikoni kontrollin statukselle.
 * Tämä funktio etsii kontrollia edeltävän div:in ja sitä seuraavan spanin.
 * Kun se löytää moisen, se asettaa oikeat luokat kontrollille ja sen kavereille riippuen
 * siitä onko kontrollin tila "validi" vai "epävalidi"
 * */
function check(kontrolli) {
    parent = $(kontrolli).parent('div');
	spani = $(kontrolli).next('span');
    if(!spani.hasClass("glyphicon")) {
        spani=parent.children("span.glyphicon");
    }
	if ($(kontrolli).get(0).checkValidity()===true) {
        if($(parent).hasClass("has-error")) {
            $(parent).removeClass("has-error ");
            if(spani) {
                $(spani).removeClass("glyphicon-remove");
            }
        }
        $(parent).addClass("has-success");
        if(spani) {
            $(spani).addClass("glyphicon-ok");
        }
        if(!$(parent).hasClass("has-feedback")) {
            $(parent).addClass("has-feedback");
        }
							
    } else {
        if($(parent).hasClass("has-success")) {
            $(parent).removeClass("has-success");
            if(spani) {
                $(spani).removeClass("glyphicon-ok");
            }
        }
        $(parent).addClass("has-error");
        if(spani) {
            $(spani).addClass("glyphicon-remove");
        }
        if(!$(parent).hasClass("has-feedback")) {
            $(parent).addClass("has-feedback");
        }
                        
    }
}