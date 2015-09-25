//note this file MUST be loaded before any depending classes @todo resolve model load order
namespace common.models {

    export interface IModel {
        getAttributes(includeUnderscoredKeys?:boolean):Object;
        setExists(exists:boolean):void;
        exists():boolean;
    }

    export interface IModelClass {
        new(data?:any, exists?:boolean):IModel;
    }

    export interface IModelFactory {
        (data:any, exists?:boolean):IModel;
    }

    export interface IHydrateFunction {
        (data:any, exists:boolean):any;
    }

    export interface INestedEntityMap {
        [key:string] : IModelClass | IHydrateFunction;
    }

    export interface IAttributeCastFunction {
        (value:any):any;
    }

    export interface IAttributeCastMap {
        [key:string] : IAttributeCastFunction;
    }

    export abstract class AbstractModel implements IModel {

        protected __nestedEntityMap:INestedEntityMap;
        protected __attributeCastMap:IAttributeCastMap;
        private __exists:boolean;

        constructor(data?:any, exists:boolean = false) {
            this.hydrate(data, exists);

            Object.defineProperty(this, "__exists", {
                enumerable: false,
                writable: true,
                value: exists,
            });
        }

        /**
         * Assign the properties of the model from the init data
         * @param data
         * @param exists
         */
        protected hydrate(data:any, exists:boolean) {
            if (_.isObject(data)) {

                _.transform(data, (model, value, key) => {

                    if(_.has(this.__attributeCastMap, key)) {
                        model[key] = this.__attributeCastMap[key](value);
                    } else {
                        model[key] = value;
                    }

                }, this);

                if (!_.isEmpty(this.__nestedEntityMap)) {
                    this.hydrateNested(data, exists);
                }
            }

        }

        /**
         * Converts a date time
         * @param value
         * @returns {Date}
         */
        protected castDate(value:string):Date {
            return moment(value).toDate();
        }

        /**
         * Converts a moment object
         * @param value
         * @returns {Moment}
         */
        protected castMoment(value:string):moment.Moment {
            return moment(value);
        }

        /**
         * Checks to see if an entity implements interface IModelClass.
         *
         * Note: This is valid Typescript (1.6), change when PHPStorm gets an update:
         *
         * private isModelClass(entity: any):entity is IModelClass { ... }
         *
         * @param entity
         */
        private isModelClass(entity: any):boolean {
            return entity.prototype && entity.prototype instanceof AbstractModel;
        }

        /**
         * Find all the nested entities and hydrate them into model instances
         * @param data
         * @param exists
         */
        protected hydrateNested(data:any, exists:boolean){

            _.forIn(this.__nestedEntityMap, (nestedObject:IModelClass|IHydrateFunction, nestedKey:string) => {

                let nestedData = null;

                if(this.isModelClass(nestedObject)) {
                    if(_.has(data, nestedKey) && !_.isNull(data[nestedKey])) {
                        if (_.isArray(data[nestedKey])){
                            nestedData = _.map(data[nestedKey], (entityData) => this.hydrateModel(entityData, (<IModelClass>nestedObject), exists));
                        } else if (_.isObject(data[nestedKey])) {
                            nestedData = this.hydrateModel(data[nestedKey], (<IModelClass>nestedObject), exists);
                        }
                    }
                }
                else {
                    nestedData = (<IHydrateFunction>nestedObject)(data, exists);
                }

                this[nestedKey] = nestedData;

            });

        }

        /**
         * Get a new instance of a model from data
         * @param data
         * @param Model
         * @returns {common.models.AbstractModel}
         * @param exists
         */
        private hydrateModel(data:any, Model:IModelClass, exists:boolean){

            return new Model(data, exists);
        }

        /**
         * Get all enumerable attributes of the model, by default excluding all keys starting with an _underscore
         * @param includeUnderscoredKeys
         * @returns {any}
         */
        public getAttributes(includeUnderscoredKeys:boolean = false):Object{

            let allAttributes = _.clone(this);

            let publicAttributes = _.omit(allAttributes, (value, key) => {
                return _.startsWith(key, '__');
            });

            if (includeUnderscoredKeys){
                return publicAttributes;
            }

            return _.omit(publicAttributes, (value, key) => {
                return _.startsWith(key, '_');
            });

        }

        /**
         * Get if the model exists in remote api
         * @returns {boolean}
         */
        public exists():boolean{
            return this.__exists;
        }

        /**
         * Set if the model exists
         * @param exists
         */
        public setExists(exists:boolean):void{
            this.__exists = exists;
        }

        /**
         * Generates a UUID using lil:
         * https://github.com/lil-js/uuid
         * @returns {string}
         */
        public static generateUUID():string {
            return lil.uuid();
        }

    }

}



