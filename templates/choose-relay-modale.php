<div id="relayModal" title="Sélectionnez un point relais" style="display: none;">
    <div class="rc-container-fluid">
        <div class="rc-row">
            <div class="rc-col-sm-6 divCompleteAdress">
                <input type="text" id="tbCompleteAdress" class="rc-form-control" placeholder="Adresse, code postal, ville..." autocomplete="off" />
                <input type="hidden" id="hdLon" />
                <input type="hidden" id="hdLat" />
            </div>
            <div class="rc-col-sm-3 divBoutonOK">
                <button id="btnSearch" class="rc-btn boutonOK" type="button">
                    Trouver mon relais
                    <i class="fa-solid fa-arrow-right" style="font-family: 'Font Awesome 6 Free'; font-weight: 900;"></i>
                </button>
            </div>
            <div class="rc-col-sm-3 rc-hidden-xs divLogoRelaisColis">
                <img alt="logo Relais Colis" id="imgRelaisColis" src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/img/livemapping/rc_long_logo.png'); ?>" />
            </div>
        </div>
        <div class="rc-row">
            <div class="rc-col-sm-12 error">
                <div id="lstError">
                </div>
            </div>
        </div>
        <div class="rc-row" id="divEmplacement" style="display: none;">
            <div class="rc-col-md-2 rc-col-sm-3 lblPrecision">
                <label for="selectListAddress"><b>Précisez l'emplacement :</b></label>
            </div>
            <div class="rc-col-md-4  rc-col-sm-3">
                <select name="selectListAddress" id="selectListAddress">
                </select>
            </div>
            <div class="rc-col-md-3 rc-col-sm-3 divBtnAfficher">
                <button id="boutonAfficher" type="button" class="rc-btn boutonAfficher">Afficher
                    <span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="rc-row">
            <div class="rc-col-sm-4 divListRelais">
                <div id="lstRelais"></div>
            </div>
            <div class="rc-col-sm-8 divMap">
                <div class="mapContainer"></div>
            </div>
        </div>
    </div>
</div>