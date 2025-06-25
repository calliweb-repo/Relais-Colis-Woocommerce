jQuery(document).ready(function ($) {

    console.log('RC Choose Relay init');

    "use strict";

    // Vérification si jQuery UI est chargé
    if (typeof $.ui === "undefined" || typeof $.ui.dialog === "undefined") {
        console.error("jQuery UI Dialog non disponible. Vérifiez son inclusion.");
        return;
    }

    /*********************************/
    /******* Choose relay modal ******/
    /*********************************/

    // Ouvrir la modale au clic sur le bouton
    $(document).on("click", "#btnChooseRelay", function (e) {
        console.log("Clic détecté : ouverture de la modale");
        e.preventDefault();
        $("#relayModal").dialog("open");
    });

    /**
     * JQuery UI Dialog modal
     */
    $("#relayModal").dialog({
        autoOpen: false, // Ne pas ouvrir automatiquement
        create: function () {
            $("#relayModal").css("display", "none");
        },
        modal: true, // Bloque l'interaction avec la page derrière
        width: window.innerWidth <= 768 ? "90%" : "1200px",
        minHeight: 500,
        resizable: true,
        classes: {
            "ui-dialog": "rc-ui-dialog",
        },
        draggable: true,
        closeOnEscape: true,
        buttons: {
            "Fermer": function () {
                $(this).dialog("close");
            }
        },
        open: function () {
            console.log("Modale ouverte !");
            // Always prefill address before modal opening
            prefillShippingAddress();

            // Load map only once
            if (!$("#relayModal").hasClass("loaded")) {
                $("#relayModal").addClass("loaded");
                LancerCarte();
            }
        },
        close: function () {

            console.log("Modale fermée !");
        }
    });


    /**
     * Prefilled search with WooCommerce address passed via wp_localize_script(),
     * fallback to DOM fields if incomplete.
     */
    function prefillShippingAddress() {
        let fallback = rc_choose_relay.rc_shipping_address || {};
        console.log('fallback', fallback);
        // On commence par récupérer les valeurs saisies dans le DOM
        let address = document.querySelector('#shipping-address_1')?.value || '';
        let postcode = document.querySelector('#shipping-postcode')?.value || '';
        let city = document.querySelector('#shipping-city')?.value || '';

        // Si l'utilisateur n'a rien saisi, on complète depuis le backend
        if (!address) {
            address = fallback.address || '';
        }
        if (!postcode) {
            postcode = fallback.postcode || '';
        }
        if (!city) {
            city = fallback.city || '';
        }

        let fullAddress = `${address}, ${postcode} ${city}`.trim();

        if (postcode && city) {
            console.log('prefill ok '+ fullAddress);
            $("#tbCompleteAdress").val(fullAddress);
            searchForRelay();
        }

        console.log('prefill ko');
    }

    /**
     * boutonAfficher On click extracted from template
     * @param strParamName
     * @returns {string}
     * @constructor
     */

    $("#boutonAfficher").on("click", function () {
        var selectedValue = $("#selectListAddress").val();
        LeverAmbiguite(selectedValue);
    });

    /******************************/
    /**** Map and search form *****/
    /******************************/

    var map = null;
    var featureLayer;
    const usePrecisionFeature = "1";
    var ensCode = rc_choose_relay.map_c2c_enscode;
    var apiKey = rc_choose_relay.map_c2c_apikey;
    const nbRelaisColis = 30;//nombre de points relais à afficher sur la carte
    const rayonRecherche = 100000;//le rayon de recherche des POIs, de préférence laisser cette valeur à 100000
    var delaiLivJour = 5; // délai de livraison
    var relaisActifouTF = "1"; //Si la valeur est à 1, On ne retourne que les relais actifs ou temporairement fermé, sinon on retourne tous les relais y compris les créés
    var maxZoom = 19;
    var minZoom = 5;
    var zoom = 14;
    var relaisColisMax = rc_choose_relay.relaisColisMax;//Uniquement des Relais Max ? si oui mettre 1
    var relaisCodeCountry = RetrieveParameterFromUrlSimple("relaisCodeCountry").trim().toUpperCase();//Localisation des relais FRA ou BEL ou MCO.
    var relaisColisSmart = RetrieveParameterFromUrlSimple("relaisColisSmart").trim();
    var adresseCodeCountry = RetrieveParameterFromUrlSimple("adresseCodeCountry").trim().toUpperCase();
    var clientAddress = RetrieveParameterFromUrlSimple("clientAddress").trim();
    var activity = RetrieveParameterFromUrlSimple("activity").trim();//DRV si on recherche des relais Drive. Toute autre valeur donnera des relais classiques
    const iconIci = rc_choose_relay.img_livemapping_path+"VousEtesIci.gif";
    const osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    const osmAttrib = 'Map data © OpenStreetMap contributors';
    const paysLimitrophes = ['France', 'Belgique', 'Monaco', 'Espagne', 'Italie', 'Allemagne', 'Luxembourg', 'Suisse'];
    const wsRelaisProchesUrl = 'https://service.relaiscolis.com/wslisterelaisproches/RelaisProches/Liste?key=' + apiKey;
    const osmgeocodingUrl = 'https://nominatim.openstreetmap.org/search?q=';
    const gouvgeocodingUrl = 'https://api-adresse.data.gouv.fr/search/?q=';
    const nbCaractersMax = 50;


    function RetrieveParameterFromUrlSimple(strParamName) {
        var strReturn = "";
        var strHref = window.location.href;
        if (strHref.indexOf("?") > -1) {
            var strQueryString = strHref.substr(strHref.indexOf("?")).toLowerCase();
            var aQueryString = strQueryString.split("&");
            for (var iParam = 0; iParam < aQueryString.length; iParam++) {
                if (aQueryString[iParam].indexOf(strParamName.toLowerCase() + "=") > -1) {
                    var aParam = aQueryString[iParam].split("=");
                    strReturn = aParam[1] + " ";
                    break;
                }
            }
        }
        return decodeURI(strReturn);
    }

    function RetrieveParameterFromUrl(strParamName) {
        var url = new URL(window.location.href);
        var result = url.searchParams.get(strParamName);
        return result;
    }

    function Init() {
        var address = RetrieveParameterFromUrlSimple("clientAddress");
        if (address != "") {
            document.getElementById("tbCompleteAdress").value = address.toUpperCase();

            relaisCodeCountry = relaisCodeCountry.toUpperCase();
            if (relaisCodeCountry != "") {
                if (relaisCodeCountry != "BEL" && relaisCodeCountry != "FRA" && relaisCodeCountry != "MCO")
                    return "KO";
            } else {
                relaisCodeCountry = "FRA";
            }
            return "OK"
        } else {
            return "KO";
        }
    }

    /**
     * Launch the map,
     * And init relays
     * @returns {boolean}
     * @constructor
     */
    function LancerCarte() {
        ParamOK = Init();
        if (ParamOK == "OK") {
            if (clientAddress == "")
                return false;

            if (adresseCodeCountry != "" || adresseCodeCountry != "FRA")
                GetPoisListGeocodingByOSM(clientAddress);
            else
                GetPoisListGeocodingByGouvFr(clientAddress);
        }
    };

    /**
     * Search for a relay point, from an address
     */
    function searchForRelay() {
        var adresseSaisie = $("#tbCompleteAdress").val();
        console.log('searchForRelay '+ adresseSaisie);
        var lon = $("#hdLon").val();
        //var lon = '';
        var lat = $("#hdLat").val();

        if (adresseSaisie == "") {
            return;
        } else {
            console.log('adresseSaisie is not empty');
            GetPoisListGeocodingByOSM(adresseSaisie);
        }

        // if (lon == "") {
        //     console.log('lon is empty');
        //     if (adresseSaisie == "") {
        //         return;
        //     } else {
        //         console.log('adresseSaisie is not empty');
        //         GetPoisListGeocodingByOSM(adresseSaisie);
        //     }
        // }
    }

    $(document).on('click', "#btnSearch", searchForRelay);

    /**
     * Autocomplete search on address input
     * gouv.fr API call -> https://api-adresse.data.gouv.fr/search/?q=
     */
    $(function () {
        $("#tbCompleteAdress").autocomplete(
            {
                source: function (request, response) {
                    var websUrl = gouvgeocodingUrl + request.term + "&autocomplete=1";
                    var protocol = window.location.protocol;
                    if (protocol == "http") {
                        websUrl = gouvgeocodingUrl.replace("https", "http") + request.term + "&autocomplete=1";
                    }
                    $("#hdLon").val("");
                    $("#hdLat").val("");
                    $.ajax({
                        type: 'GET',
                        url: websUrl,
                        contentType: 'application/json',
                        dataType: 'json',
                        success: function (data) {
                            var itemArray = new Array();
                            $.each(data.features, function (index, adress) {
                                var city = adress.properties.city;
                                var postcode = adress.properties.postcode;
                                var context = adress.properties.context;
                                var label = adress.properties.label;
                                itemArray[index] = { label: postcode + ' ' + city + ' - ' + adress.properties.name, value: adress.geometry, data: adress }
                            });
                            response(itemArray);
                        },
                        error: function (error) {
                            console.log("FAIL....=================");
                        }
                    });
                },
                select: function (event, ui) {
                    $("#tbCompleteAdress").val(ui.item.label);
                    if (ui.item.value.coordinates.length > 0) {
                        $("#hdLon").val(ui.item.value.coordinates[0]);
                        $("#hdLat").val(ui.item.value.coordinates[1]);
                        HideEmplacement(1);
                        GetPoisListNearTo($("#hdLon").val(), $("#hdLat").val(), ensCode);
                    }
                    else {
                        $("#hdLon").val("");
                        $("#hdLat").val("");
                    }
                    return false;
                },
                focus: function (event, ui) {
                    $("#tbCompleteAdress").val(ui.item.label);
                    return false;
                },
                minLength: 3
            });
    });

    $(document).keyup(function (e) {
        var key = e.which;
        if (key == 13) {
            var adresseSaisie = $("#tbCompleteAdress").val();
            var lon = $("#hdLon").val();

            if (lon == "") {
                if (adresseSaisie == "") {
                    return;
                } else {
                    GetPoisListGeocodingByOSM(adresseSaisie);
                }
            }
        }
    });

    /**
     * Search for relay near an address, by lon/lat
     * RelaisColis API Call to https://service.relaiscolis.com/wslisterelaisproches/RelaisProches/Liste?key=
     * @param lon
     * @param lat
     * @param ensCode
     * @constructor
     */
    function GetPoisListNearTo(lon, lat, ensCode) {
        $(".mapContainer").html("<div id='map' style='width: 100%; height: 100%;'></div>");
        map = L.map('map', { center: new L.LatLng(lat, lon), maxZoom: maxZoom, minZoom: minZoom });
        var tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: maxZoom,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        if (featureLayer != undefined)
            map.removeLayer(featureLayer);

        $("#lstError").html("");
        var websUrl = wsRelaisProchesUrl;
        var request = {
            Lon: lon,
            Lat: lat,
            EnsCode: ensCode,
            RelaisMax: relaisColisMax,
            RelaisSmart: relaisColisSmart,
            RelaisActifouTF: relaisActifouTF,
            RelaisCodeCountry: relaisCodeCountry,
            RayonRecherche: rayonRecherche,
            Delailogistique: delaiLivJour,
            AdresseSaisie: $("#tbCompleteAdress").val(),
            NbRelais: nbRelaisColis,
            Activity: activity
        };

        $.ajax({
            type: 'POST',
            url: websUrl,
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify(request),
            success: function (datas, status, req) {
                var listErrors = datas.ErrorsList;
                if (listErrors.length > 0) {
                    console.log(datas.responseText);
                    console.log(datas.ErrorsList[0].ErrorDescription);
                    $("#lstError").html("<div>" + datas.ErrorsList[0].ErrorDescription + "</div>");
                }
                if (status == "success") {
                    PlacerLesPointsRelais(map, datas, lat, lon);
                }
                if (datas.PoisList.length == 0) {
                    alert("Aucun relais trouvé !");
                }
            },
            error: function (datas, status, req) {
                alert(datas.responseJSON.Message + " - " + datas.responseJSON.ExceptionMessage);
            }
        });
    }

    /**
     * Search for relay near an address, by OSM
     * OpenstreetMap API Call to https://nominatim.openstreetmap.org/search?q=
     * @param params
     * @constructor
     */
    function GetPoisListGeocodingByOSM(params) {
        var websUrl = osmgeocodingUrl + params + '&format=json';
        $("#lstRelais").html("");
        if (map != undefined) {
            map.remove();
        }

        if (window.location.protocol == "http") {
            websUrl = websUrl.replace("https", "http");
        }
        $.getJSON(websUrl, function (adressList) {
            var newListAdress = [];
            $.each(adressList, function (index, adress) {
                var tableau = adress.display_name.split(',');
                if (paysLimitrophes.indexOf(tableau[tableau.length - 1].replace(/^\s+/, "")) >= 0) {
                    newListAdress.push(adress);
                }
            });
            
            // Vérifier si _.orderBy existe, sinon utiliser une alternative
            var adressListSorted = newListAdress.slice().sort(function(a, b) {
                return b.importance - a.importance;
            });
        
            var nbResults = adressListSorted.length;
            switch (nbResults) {
                case 0:
                    HideEmplacement(1);
                    var newparams = params.match(/[0-9]{5}/);
                    if(newparams == null || (newparams != null && newparams.length == 0))
                    {
                        alert('Whoops : adresse non trouvée');
                    }
                    else
                    {
                        websUrl = osmgeocodingUrl + newparams[0] + '&format=json';
                        $.getJSON(websUrl, function (adressList) {
                            newListAdress = [];
                            $.each(adressList, function (index, adress) {
                                tableau = adress.display_name.split(',');
                                if (paysLimitrophes.indexOf(tableau[tableau.length - 1].replace(/^\s+/, "")) >= 0) {
                                    newListAdress.push(adress);
                                }
                            });
                            var adressListSorted = newListAdress.slice().sort(function(a, b) {
                                return b.importance - a.importance;
                            });
                            nbResults = adressListSorted.length;
                            if(nbResults == 0)
                                alert('Whoops : adresse non trouvée');
                            else
                            {
                                var adress = adressListSorted[0];
                                $("#hdLon").val(adress.lon);
                                $("#hdLat").val(adress.lat);
                                GetPoisListNearTo(adress.lon, adress.lat, ensCode);
                            }
                        });
                    }
                    break;
                case 1:
                    HideEmplacement(1);
                    var adress = adressListSorted[0];
                    $("#hdLon").val(adress.lon);
                    $("#hdLat").val(adress.lat);
                    GetPoisListNearTo(adress.lon, adress.lat, ensCode);
                    break;
                default:
                    HideEmplacement(1);
                    var listeFinale = new Array();
                    var selectListAddress = $("#selectListAddress");
                    $('#selectListAddress')[0].options.length = 0;
                    $.each(adressListSorted, function (index, adress) {
                        var out = adress.display_name;
                        if(out.length > nbCaractersMax)
                            out = out.substring(0, nbCaractersMax)+"...";
                        var coordXY = '(' + adress.lat + ', ' + adress.lon + ')';
                        var new_option = new Option(out, coordXY);
                        new_option.title = adress.display_name;
                        selectListAddress.append(new_option);
                        out = '';
                    });
                    if(usePrecisionFeature =="1")
                    {
                        var adress = adressListSorted[0];
                        $("#hdLon").val(adress.lon);
                        $("#hdLat").val(adress.lat);
                        GetPoisListNearTo(adress.lon, adress.lat, ensCode);
                    }
                    break;
            }
        });
    }

    /**
     * Search for relay near an address, using gouv.fr service, by params
     * gouv.fr API Call to https://api-adresse.data.gouv.fr/search/?q=
     * @param params
     * @constructor
     */
    function GetPoisListGeocodingByGouvFr(params) {
        var websUrl = gouvgeocodingUrl + params + '&autocomplete=0';

        if (window.location.protocol == "http") {
            websUrl = gouvgeocodingUrl.replace("https", "http") + params + '&autocomplete=0';
        }

        $("#lstRelais").html("");
        $.getJSON(websUrl, function (data) {
            var nbResults = data.features.length;
            var out = '';
            var coordXY = '';
            switch (nbResults) {
                case 0:
                    HideEmplacement(1);
                    alert('Whoops : adresse non trouvée !');
                    break;
                case 1:
                    HideEmplacement(1);
                    var adress = data.features[0];
                    $("#hdLon").val(adress.geometry.coordinates[0]);
                    $("#hdLat").val(adress.geometry.coordinates[1]);
                    GetPoisListNearTo(adress.geometry.coordinates[0], adress.geometry.coordinates[1], ensCode);
                    break;
                default:
                    HideEmplacement(0);
                    var selectListAddress = $("#selectListAddress");
                    $('#selectListAddress')[0].options.length = 0;
                    $.each(data.features, function (index, adress) {
                        var lon = adress.geometry.coordinates[0];
                        var lat = adress.geometry.coordinates[1];
                        out = adress.properties.name + ' ' + adress.properties.postcode + ' ' + adress.properties.city;
                        coordXY = '(' + lat + ', ' + lon + ')';
                        var title = out;
                        if(out.length > nbCaractersMax)
                            out = out.substring(0, nbCaractersMax)+"...";
                        var new_option = new Option(out, coordXY);
                        new_option.title = title;
                        selectListAddress.append(new_option);
                        out = '';
                    });
                    $("#tbCompleteAdress").focus();
                    if(usePrecisionFeature =="1")
                    {
                        var adress = data.features[0];
                        $("#hdLon").val(adress.geometry.coordinates[0]);
                        $("#hdLat").val(adress.geometry.coordinates[1]);
                        GetPoisListNearTo(adress.geometry.coordinates[0], adress.geometry.coordinates[1], ensCode);
                        $("#tbCompleteAdress").blur();
                    }
                    break;
            }
        });
    }

    /**
     * Render relay on map
     * @param map
     * @param data
     * @param lat
     * @param lon
     * @constructor
     */
    function PlacerLesPointsRelais(map, data, lat, lon) {
        var pinContent  = "";
        $("#lstRelais").html("");
        var markerList = [];
        var iconHere = L.icon({
            iconUrl: iconIci,
            iconSize: [25, 25]
        });
        var markerIci = L.marker([lat, lon], { icon: iconHere }).bindTooltip('<b style="font-size:12px">Vous êtes ici</b>');
        markerList.push(markerIci);

        $.each(data.PoisList, function (index, relais) {
            var popupHtml = GeneratePopup(relais);
            var iconRelais = L.icon({
                iconUrl: rc_choose_relay.img_livemapping_path + relais.IconeLogo,
                iconSize: [25, 25]
            });

            var PoiHtmlList = GeneratePoisSimpleList(relais, index);
            var CurrentList = $("#lstRelais").html();
            $("#lstRelais").html(CurrentList + PoiHtmlList);

            var markerIcon = L.icon({
                iconUrl: rc_choose_relay.img_livemapping_path+ relais.IconeLogo,
                iconSize: [25, 25],
                className: "iconMap"
            });

            if(relais.AffichageLien == "OK")
                pinContent = '<div class="labelMap" style="background-image:url('+rc_choose_relay.img_livemapping_path+relais.IconeLogo + ')" title="' + relais.Nomdepositaire + '"><b class="pins">' + (index + 1) + '</b></div>';
            else
                pinContent = '<div class="labelMapFerme" style="background-image:url('+rc_choose_relay.img_livemapping_path+ relais.IconeLogo + ')" title="' + relais.Nomdepositaire + '"><b class="pins">' + (index + 1) + '</b></div>';

            var markerText = L.divIcon(
                {
                    className: 'text',
                    permanent: true,
                    html: pinContent,
                    iconSize: [25, 25]
                });

            var label = L.marker([relais.Lat, relais.Lon], { icon: markerText });
            label.bindPopup(popupHtml);
            markerList.push(label);
        });
        if (markerList.length > 0) {
            var group = L.featureGroup(markerList).addTo(map);
            map.fitBounds(group.getBounds());
        }

    }

    /**
     * Generate a bloc with relay detail outside the map, with a select button
     * @param relais
     * @returns {string}
     * @constructor
     */
    function GeneratePopup(relais) {
        var poiShowing = relais.AffichageLien;
        var poiUrl = GenerateUrlSuite(relais);

        var poiHTML = "<div class='card rc-mb-3'>"
            + "<div class='rc-row rc-g-0'>"
            + "<div class='rc-col-md-5'>"
            + "<img id='relais_img' class='relais_img' title='photo du relais' src='" + relais.Photopath + "' />"
            + "</div>"
            + "<div class='rc-col-md-7'>"
            + "<div class='card-body'>"
            + "<h5 class='card-title'><div class='nomrelais'>"
            + relais.Nomdepositaire.toUpperCase() + " (" + relais.Distance + "m)"
            + "</div></h5 >"
            + "<p class='card-text cardText'>" + relais.Geocoadresse + "</p>"
            + "<p class='card-text cardText'>" + relais.Postalcode + " " + relais.Commune + "</p>"
            + "</div>"
            + "</div>"
            + "</div>"
            + "</div><br>";

        poiHTML += "<table class='tableH horaire'>"
            + "<tr><td colspan='3'><b>Horaires d'ouverture</b></td></tr>"
            + "<tr><td class='jour'>Lundi</td><td class='tdhoraire'>" + relais.Horairelundimatin + "</td><td class='tdhoraire'>" + relais.Horairelundiapm + "</td></tr>"
            + "<tr><td class='jour'>Mardi</td><td class='tdhoraire'>" + relais.Horairemardimatin + "</td><td class='tdhoraire'>" + relais.Horairemardiapm + "</td></tr>"
            + "<tr><td class='jour'>Mercredi</td><td class='tdhoraire'>" + relais.Horairemercredimatin + "</td><td class='tdhoraire'>" + relais.Horairemercrediapm + "</td></tr>"
            + "<tr><td class='jour'>Jeudi</td><td class='tdhoraire'>" + relais.Horairejeudimatin + "</td><td class='tdhoraire'>" + relais.Horairejeudiapm + "</td></tr>"
            + "<tr><td class='jour'>Vendredi</td><td class='tdhoraire'>" + relais.Horairevendredimatin + "</td><td class='tdhoraire'>" + relais.Horairevendrediapm + "</td></tr>"
            + "<tr><td class='jour'>Samedi</td><td class='tdhoraire'>" + relais.Horairesamedimatin + "</td><td class='tdhoraire'>" + relais.Horairesamediapm + "</td></tr>"
            + "<tr><td class='jour'>Dimanche</td><td class='tdhoraire'>" + relais.Horairedimanchematin + "</td><td class='tdhoraire'>" + relais.Horairedimancheapm + "</td></tr>";

        if (poiShowing === "OK") {

            poiHTML += "<tr><td></td><td class='selection-relais-td' colspan='2'>";

            // Stocker tout l'objet dans data-relay-info
            let relaisJson = btoa(JSON.stringify(relais)); // Encode en Base64

            poiHTML += `<button type="button" class="select-button select-button-popup-relais" 
                        data-relay-name="${relais.Nomdepositaire}" 
                        data-relay-address="${relais.Geocoadresse}" 
                        data-relay-postalcode="${relais.Postalcode}" 
                        data-relay-commune="${relais.Commune}"
                        data-relay-info="${relaisJson}">
                        Sélectionner
                    </button>`;

            /*poiHTML += "<div style=''><a target='_parent' href='validation.html?codeRelais=" + relais.Xeett + "&nomRelais=" + escape(relais.Nomrelais)
                + poiUrl
                + "'><button type='button' class='select-button select-button-popup-relais'>"
                +"<span aria-hidden='true'>Sélectionner</span>"
                +"<span class='select-button' style='float:right' aria-hidden='true'></span>"
                +"</button></a></div>";*/

            poiHTML += "</td></tr></table>";
        } else {
            poiHTML += "</table>";
            poiHTML += "<div class='relaisEnConges'>";
            if (relais.Datepremiercolis === "" && relais.Datefermeture !== "")
                poiHTML += "Relais fermé le " + relais.Datefermeture + "</div>";
            else
                poiHTML += "Relais en congés du " + relais.Datefermeture + " au " + relais.Datepremiercolis + "</div>";
        }
        return poiHTML;
    }

    /**
     * Choose a relay, and update WooCommerce
     */
    $(document).on("click", ".select-button-popup-relais", function (e) {
        e.preventDefault();

        let relayName = $(this).data("relay-name");
        let relayAddress = $(this).data("relay-address");
        let relayPostalcode = $(this).data("relay-postalcode");
        let relayCommune = $(this).data("relay-commune");

        // Update information on checkout page
        $("#selected-relay-name").text(relayName);
        $("#selected-relay-address").text(relayAddress);
        $("#selected-relay-zip-city").text(relayPostalcode+' '+relayCommune);
        $("#selected-relay-info").fadeIn();
        $("#relais-colis-block").find('button').text('Choisir un nouveau Point Relais Colis');

        // Récupération de l'objet complet stocké dans le bouton pour envoi AJAX
        let relayData = $(this).attr("data-relay-info"); // Récupérer la chaîne Base64
        let relayObj = JSON.parse(atob(relayData)); // Décoder et parser en objet JS

        console.log("✅ Relais sélectionné :", relayName, relayAddress, relayPostalcode, relayCommune, relayObj);
        console.log(rc_choose_relay.nonce);

        // Send infos on custom relais colis AJAX REST API
        $.ajax({
            url: rc_choose_relay.ajax_url, // Use the localized AJAX URL
            dataType: 'json',
            method: 'POST',
            delay: 250,
            data: {
                action: 'update_relay', // Nom de l'action WordPress
                rc_relay_data: relayObj,   // Envoi de l'objet entier
                nonce: rc_choose_relay.nonce // Ajout du nonce pour la sécurité
            },
            beforeSend: function (xhr) {
                console.log("🔄 Envoi du relais colis :", {
                    relay_name: relayName,
                    relay_address: relayAddress,
                    relay_postalcode: relayPostalcode,
                    relay_commune: relayCommune,
                    nonce: rc_choose_relay.nonce
                });
            },
            success: function (response) {
                console.log("✅ Relais enregistré avec succès :", response);
            },
            error: function (xhr, textStatus, errorThrown) {
                console.error("⚠️ Erreur lors de l'enregistrement du relais :", xhr.responseText);
            }
        });

        // Fermer la modale après sélection
        $("#relayModal").dialog("close");
    });


    function poiLocate(Lat, Lon) {
        map.setView([Lat, Lon]);
    }

    function LeverAmbiguite(index) {
        var param = $('#selectListAddress option:selected').val();
        var indexfin = param.length;
        var indexdeb = param.indexOf("(");
        var indexsep = param.indexOf(",");
        var Lat = parseFloat(param.substr(indexdeb + 1, indexsep - indexdeb - 1));
        var Lon = parseFloat(param.substr(indexsep + 1, indexfin - 1));
        GetPoisListNearTo(Lon, Lat, ensCode);
    }

    function GeneratePoisSimpleList(relais, index) {
        var poiShowing = relais.AffichageLien;
        var poiUrl = GenerateUrlSuite(relais);
        var poiHTMLLst = '';
        poiHTMLLst += "<div class='divDetails'>"
            + "<a style='color:black;text-decoration:none' href='#' onclick='javascript:poiLocate(\""
            + relais.Lat + "\",\"" + relais.Lon + "\");'><div class='picto locator-list-item-picto'><b class='index_picto'>"
            + (index + 1) + "</b></div><label class='nomrelais'>" + " " + relais.Nomrelais.toUpperCase() + " (" + relais.Distance + "m)"
            + "</label></a><div class='item-picto'>";

        poiHTMLLst += "<div class='locator-list-item-detail'><small class='small link--active'>ID : " + relais.Xeett + "</small><br><div class='adressrelais'>"

        poiHTMLLst += relais.Geocoadresse;
        poiHTMLLst += "<br/>" + relais.Postalcode + " " + relais.Commune + "</div></div>";
        poiHTMLLst += "<b class='cfs'>Horaires d'ouverture</b>";

        poiHTMLLst += "<div><table id='horaires_" + (index + 1) + "' class='horaireList'>"
            + "<tr><td class='classic'>Lundi</td><td class='classic'>" + relais.Horairelundimatin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairelundiapm.replace('-',' - ') + "</td></tr>"
            + "<tr><td class='classic'>Mardi</td><td class='classic'>" + relais.Horairemardimatin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairemardiapm.replace('-',' - ') + "</td></tr>"
            + "<tr><td class='classic'>Mercredi</td><td class='classic'>" + relais.Horairemercredimatin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairemercrediapm.replace('-',' - ') + "</td></tr>"
            + "<tr><td class='classic'>Jeudi</td><td class='classic'>" + relais.Horairejeudimatin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairejeudiapm.replace('-',' - ') + "</td></tr>"
            + "<tr><td class='classic'>Vendredi</td><td class='classic'>" + relais.Horairevendredimatin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairevendrediapm.replace('-',' - ') + "</td></tr>"
            + "<tr><td class='classic'>Samedi</td><td class='classic'>" + relais.Horairesamedimatin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairesamediapm.replace('-',' - ') + "</td></tr>"
            + "<tr><td class='classic'>Dimanche</td><td class='classic'>" + relais.Horairedimanchematin.replace('-',' - ') + "</td><td class='classic'>" + relais.Horairedimancheapm.replace('-',' - ') + "</td></tr>";

        if(poiShowing== "OK"){
            poiHTMLLst += "<tr><td></td><td></td><td class='selection-relais-td' >";

            // Stocker tout l'objet dans data-relay-info
            let relaisJson = btoa(JSON.stringify(relais)); // Encode en Base64

            poiHTMLLst += `<button type="button" class="select-button select-button-popup-relais" 
                        data-relay-name="${relais.Nomdepositaire}" 
                        data-relay-address="${relais.Geocoadresse}" 
                        data-relay-postalcode="${relais.Postalcode}" 
                        data-relay-commune="${relais.Commune}"
                        data-relay-info="${relaisJson}">
                        Sélectionner
                    </button>`;
/*
            poiHTMLLst += "<div class='divLink'><a target='_parent' href='validation.html?codeRelais=" + relais.Xeett + "&nomRelais=" + escape(relais.Nomrelais)
                + poiUrl
                + "'><button type='button' class='select-button' style='padding: 4px;'>"
                +"<span aria-hidden='true'>Sélectionner</span>"
                +"<span class='select-button' style='float:right' aria-hidden='true'></span>"
                +"</button></a></div>";

            poiHTMLLst += "</td></tr></table></div>";*/
        }
        else
        {
            poiHTMLLst += "</table></div>";
            poiHTMLLst += "<div class='relaisEnConges'>";
            if(relais.Datepremiercolis == "" && relais.Datefermeture != "")
                poiHTMLLst+= "Relais fermé le " + relais.Datefermeture + "</div>";
            else
                poiHTMLLst+= "Relais en congés du " + relais.Datefermeture + " au " + relais.Datepremiercolis + "</div>";
        }

        poiHTMLLst += "</div></div>";
        return poiHTMLLst;
    }

    function GenerateUrlSuite(relais) {
        var urlSuite = "&relaisAdresse=" + escape(relais.Geocoadresse)
            + "&relaisCodePostal=" + relais.Postalcode + "&relaisCity=" + relais.Commune
            + "&ouvLun=" + relais.Horairelundimatin + "@" + relais.Horairelundiapm
            + "&ouvMar=" + relais.Horairemardimatin + "@" + relais.Horairemardiapm
            + "&ouvMer=" + relais.Horairemercredimatin + "@" + relais.Horairemercrediapm
            + "&ouvJeu=" + relais.Horairejeudimatin + "@" + relais.Horairejeudiapm
            + "&ouvVen=" + relais.Horairevendredimatin + "@" + relais.Horairevendrediapm
            + "&ouvSam=" + relais.Horairesamedimatin + "@" + relais.Horairesamediapm
            + "&ouvDim=" + relais.Horairedimanchematin + "@" + relais.Horairedimancheapm
            + "&pseudoRvc=" + relais.Pseudorvc
            + "&adresseClient=" + escape($("#tbCompleteAdress").val())
            + "&relaisColisMax=" + relaisColisMax
            + "&relaisCodeCountry=" + relais.countryISO
            + "&adresseCodeCountry=" + relais.AgenceCountryISO
            + "&agenceCode=" + relais.Agencecode + "&agenceNom=" + relais.Agencenom
            + "&agenceAdresse=" + relais.Agenceadresse1 + " " + relais.Agenceadresse2
            + "&agenceCity=" + relais.Agenceville
            + "&agenceCodePostal=" + relais.Agencecodepostal;
        return urlSuite;
    }

    function GenerateJsonSuite(relais){
        var jsonSuite = {
            relaisAdresse: relais.Geocoadresse,
            relaisCodePostal: relais.Postalcode,
            relaisCity: relais.Commune,
            ouvLun: `${relais.Horairelundimatin}@${relais.Horairelundiapm}`,
            ouvMar: `${relais.Horairemardimatin}@${relais.Horairemardiapm}`,
            ouvMer: `${relais.Horairemercredimatin}@${relais.Horairemercrediapm}`,
            ouvJeu: `${relais.Horairejeudimatin}@${relais.Horairejeudiapm}`,
            ouvVen: `${relais.Horairevendredimatin}@${relais.Horairevendrediapm}`,
            ouvSam: `${relais.Horairesamedimatin}@${relais.Horairesamediapm}`,
            ouvDim: `${relais.Horairedimanchematin}@${relais.Horairedimancheapm}`,
            pseudoRvc: relais.Pseudorvc,
            adresseClient: $("#tbCompleteAdress").val(),
            relaisColisMax: relais.Relaismax,
            relaisCodeCountry: relais.countryISO,
            adresseCodeCountry: relais.AgenceCountryISO,
            agenceCode: relais.Agencecode,
            agenceNom: relais.Agencenom,
            agenceAdresse: `${relais.Agenceadresse1} ${relais.Agenceadresse2}`,
            agenceCity: relais.Agenceville,
            agenceCodePostal: relais.Agencecodepostal
        }
        return jsonSuite
    }

    function HideEmplacement(hide) {
        if (hide == 1)
            $("#divEmplacement").attr("style", "display:none");
        else
            $("#divEmplacement").attr("style", "");
    }
});