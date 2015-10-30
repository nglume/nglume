namespace common.directives.localizableInput.dialog {

    export const namespace = 'common.directives.localizableInput.dialog';

    export interface ILocalizationMap {
        [regionCode:string] : string;
    }

    export class LocalizableInputDialogController {

        static $inject = ['localizations', 'attributeKey', 'inputNodeName', 'originalValue', 'regionService', '$mdDialog', 'ngRestAdapter'];

        public selectedIndex:number = 0;
        public localizationMap:ILocalizationMap;

        constructor(public localizations:common.models.Localization<any>[],
                    public attributeKey:string,
                    public inputNodeName:string,
                    public originalValue:string,
                    public regionService:common.services.region.RegionService,
                    private $mdDialog:ng.material.IDialogService,
                    private ngRestAdapter:NgRestAdapter.NgRestAdapterService
        ) {

            this.localizationMap = _.reduce(regionService.supportedRegions, (localizationMap, region:global.ISupportedRegion) => {
                localizationMap[region.code] = this.getLocalizationValueForRegion(region.code);
                return localizationMap;
            }, {});

        }

        private getLocalizationValueForRegion(regionCode:string):string {
            let localization =  _.find(this.localizations, {regionCode: regionCode});

            if (!localization){
                return null;
            }

            return localization.localizations[this.attributeKey];
        }

        public saveLocalizations(){

            let updatedLocalizations = _.reduce(this.localizationMap, (updatedLocalizations:common.models.Localization<any>[], translation:string, regionCode:string) => {
                if(!translation){
                    return updatedLocalizations;
                }

                let existing = _.find(this.localizations, {regionCode: regionCode});

                if(existing){
                    existing.localizations[this.attributeKey] = translation;
                    updatedLocalizations.push(existing);
                    return updatedLocalizations;
                }

                updatedLocalizations.push(new common.models.Localization<any>({
                    localizableId: this.ngRestAdapter.uuid(),
                    localizableType: null, //this is determined by the api
                    localizations: {
                        [this.attributeKey]: translation
                    },
                    regionCode: regionCode,
                }));

                return updatedLocalizations;

            }, []);

            this.$mdDialog.hide(updatedLocalizations);
        }

        /**
         * allow the user to manually close the dialog
         */
        public cancelDialog() {
            this.$mdDialog.cancel('closed');
        }

    }

    angular.module(namespace, [])
        .controller(namespace + '.controller', LocalizableInputDialogController);

}
