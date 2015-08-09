namespace common.directives.menuToggle {

    export const namespace = 'common.directives.menuToggle';

    interface IMenuToggleScope extends ng.IScope{
        isOpen():boolean;
        toggle():void;
        gotoState(stateName:string):void;
        navigationState: ng.ui.IState;
    }

    class MenuToggleDirective implements ng.IDirective {

        public restrict = 'E';
        public templateUrl = 'templates/common/directives/menuToggle/menuToggle.tpl.html';
        public replace = true;
        public scope = {
            navigationState: '='
        };

        constructor(private $timeout: ng.ITimeoutService, private $state:ng.ui.IStateService) {
        }

        public link = ($scope: IMenuToggleScope, $element: ng.IAugmentedJQuery, $attrs: ng.IAttributes, $controllers: any) => {

            let list = $element.find('md-list');
            let open = this.$state.includes($scope.navigationState.name);

            $scope.isOpen = function() {
                return open;
            };
            $scope.toggle = function() {
                open = !open;
            };

            $scope.gotoState = (stateName:string) => {
                this.$state.go(stateName);
            };

            let getTargetHeight = (element:ng.IRootElementService) => {
                element.addClass('no-transition');
                element.css('height', '');
                let targetHeight = element.prop('clientHeight');
                element.css('height', 0);
                element.removeClass('no-transition');
                return targetHeight;
            };

            $scope.$watch(
                () => {
                    return open;
                },
                (open) => {

                    let targetHeight = open ? getTargetHeight(list) : 0;

                    this.$timeout(function () {
                        list.css({ height: targetHeight + 'px' });
                    }, 0, false);

                }
            );

        };

        static factory(): ng.IDirectiveFactory {
            const directive = ($timeout: ng.ITimeoutService, $state:ng.ui.IStateService) => new MenuToggleDirective($timeout, $state);
            directive.$inject = ['$timeout', '$state'];
            return directive;
        }
    }

    angular.module(namespace, [])
        .directive('menuToggle', MenuToggleDirective.factory())
    ;


}