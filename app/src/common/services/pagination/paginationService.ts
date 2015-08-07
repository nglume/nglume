module common.services.pagination {

    export const namespace = 'common.services.pagination';

    export class PaginatorException extends common.SpiraException {}

    export interface IRangeHeaderData{
        entityName:string;
        from:number;
        to:number;
        count:number|string;
    }

    export class Paginator {

        private static defaultCount:number = 10;

        private count:number = Paginator.defaultCount;
        private currentIndex:number = 0;
        private modelFactory:common.models.IModelFactory;

        public entityCountTotal:number;

        constructor(private url:string, private ngRestAdapter:NgRestAdapter.INgRestAdapterService, private $q:ng.IQService) {

            this.modelFactory = (data:any) => data; //set a default factory that just returns the data

        }

        /**
         * Method to test when to skip the interceptor
         * @param rejection
         * @returns {boolean}
         */
        private static conditionalSkipInterceptor(rejection: ng.IHttpPromiseCallbackArg<any>): boolean {

            return rejection.status == 416;
        }
        /**
         * Build the range header
         * @param from
         * @param to
         */
        private static getRangeHeader(from:number, to:number):string {
            return 'entities=' + from + '-' + to;
        }

        /**
         * scenario
         * 34 entities
         * request 30 - 34
         * count 5
         */

        /**
         * Get the response from the collection endpoint
         * @param count
         * @param index
         */
        private getResponse(count:number, index:number = this.currentIndex):ng.IPromise<common.models.IModel[]> {

            if (this.entityCountTotal && index >= this.entityCountTotal){
                return this.$q.reject(new PaginatorException("No more results found!"));
            }

            let last = index + count - 1;
            if (this.entityCountTotal && last >= this.entityCountTotal){
                last = this.entityCountTotal - 1;
            }

            return this.ngRestAdapter
                .skipInterceptor(Paginator.conditionalSkipInterceptor)
                .get(this.url, {
                    Range: Paginator.getRangeHeader(index, last)
                }).then((response:ng.IHttpPromiseCallbackArg<any>) => {
                    this.processContentRangeHeader(response.headers);
                    return _.map(response.data, (modelData) => this.modelFactory(modelData));
                }).catch(() => {
                    return this.$q.reject(new PaginatorException("No more results found!"));
                });

        }

        /**
         * Set the default count to get responses
         * @param count
         * @returns {common.services.pagination.Paginator}
         */
        public setCount(count:number):Paginator {
            this.count = count;
            return this;
        }

        /**
         * Get the current count
         * @returns {number}
         */
        public getCount():number {
            return this.count;
        }

        /**
         * Get the next set of paginated results
         * @returns {IPromise<TResult>}
         * @param count
         */
        public getNext(count:number = this.count):ng.IPromise<any[]> {

            let responsePromise = this.getResponse(count);

            this.currentIndex += this.count;

            return responsePromise;
        }

        /**
         * Set the index back to 0 or specified index value
         */
        public reset(index:number = 0):Paginator {
            this.currentIndex = index;
            return this;
        }

        /**
         * Get the a specific range
         * @returns {ng.IPromise<any[]>}
         * @param first
         * @param last
         */
        public getRange(first:number, last:number):ng.IPromise<any[]> {

            return this.getResponse(last-first+1, first);
        }


        public setModelFactory(modelFactory:common.models.IModelFactory):Paginator{
            this.modelFactory = modelFactory;
            return this;
        }

        private processContentRangeHeader(headers:ng.IHttpHeadersGetter):void {
            let headerString = headers('Content-Range');

            if (!headerString){
                return;
            }

            let headerParts = Paginator.parseContentRangeHeader(headerString);

            if (_.isNumber(headerParts.count)){
                this.entityCountTotal = <number>headerParts.count;
            }
        }

        public static parseContentRangeHeader(headerString:String):IRangeHeaderData {
            let parts = headerString.split(/[\s\/]/);

            if (parts.length !== 3){
                throw new PaginatorException("Invalid range header; expected pattern: `entities 1-10/50`, got `"+headerString+"`");
            }

            let rangeParts = parts[1].split('-');

            if (rangeParts.length !== 2){
                throw new PaginatorException("Invalid range header; expected pattern: `entities 1-10/50`, got `"+headerString+"`");
            }

            let count:any = parts[2];
            if (!_.isNaN(Number(count))){
                count = parseInt(count);
            }

            return {
                entityName: parts[0],
                from: parseInt(rangeParts[0]),
                to: parseInt(rangeParts[1]),
                count: count,
            }

        }
    }

    export class PaginationService {

        static $inject:string[] = ['ngRestAdapter', '$q'];

        constructor(private ngRestAdapter:NgRestAdapter.INgRestAdapterService, private $q:ng.IQService) {

        }

        /**
         * Get an instance of the Paginator
         * @param url
         * @returns {common.services.pagination.Paginator}
         */
        public getPaginatorInstance(url:string):Paginator {
            return new Paginator(url, this.ngRestAdapter, this.$q);
        }

    }

    angular.module(namespace, [])
        .service('paginationService', PaginationService);

}
