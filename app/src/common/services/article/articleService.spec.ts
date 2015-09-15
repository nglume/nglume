(() => {

    let seededChance = new Chance(1);
    let fixtures = {

        getArticle():common.models.Article {

            let title = seededChance.sentence();

            return new common.models.Article({
                articleId: seededChance.guid(),
                title: title,
                body: seededChance.paragraph(),
                permalink: title.replace(' ', '-'),
                _tags: [
                    {
                        tagId: seededChance.guid(),
                        tag: seededChance.word,
                    },
                    {
                        tagId: seededChance.guid(),
                        tag: seededChance.word,
                    }
                ],
                _articleMetas: [
                    {
                        metaName: 'keyword',
                        metaContent: 'foo'
                    },
                    {
                        metaName: 'description',
                        metaContent: 'bar'
                    },
                    {
                        metaName: 'foobar',
                        metaContent: 'foobar'
                    }
                ]
            });

        },
        getArticles() {

            return chance.unique(fixtures.getArticle, 30);
        }
    };

    describe('Article Service', () => {

        let articleService:common.services.article.ArticleService;
        let $httpBackend:ng.IHttpBackendService;
        let ngRestAdapter:NgRestAdapter.NgRestAdapterService;
        let $rootScope:ng.IRootScopeService;

        beforeEach(()=> {

            module('app');

            inject((_$httpBackend_, _articleService_, _ngRestAdapter_, _$rootScope_) => {

                if (!articleService) { //dont rebind, so each test gets the singleton
                    $httpBackend = _$httpBackend_;
                    articleService = _articleService_;
                    ngRestAdapter = _ngRestAdapter_;
                    $rootScope = _$rootScope_;
                }
            });

        });

        afterEach(() => {
            $httpBackend.verifyNoOutstandingExpectation();
            $httpBackend.verifyNoOutstandingRequest();
        });

        describe('Initialisation', () => {

            it('should be an injectable service', () => {

                return expect(articleService).to.be.an('object');
            });

        });

        describe('Retrieve an article paginator', () => {

            beforeEach(() => {

                sinon.spy(ngRestAdapter, 'get');

            });

            afterEach(() => {
                (<any>ngRestAdapter.get).restore();
            });

            let articles = _.clone(fixtures.getArticles()); //get a set of articles

            it('should return the first set of articles', () => {

                $httpBackend.expectGET('/api/articles').respond(_.take(articles, 10));

                let articlePaginator = articleService.getArticlesPaginator();

                let firstSet = articlePaginator.getNext(10);

                expect(firstSet).eventually.to.be.fulfilled;
                expect(firstSet).eventually.to.deep.equal(_.take(articles, 10));

                $httpBackend.flush();

            });


        });

        describe('Get article', () => {

            let mockArticle  = fixtures.getArticle();

            it('should be able to retrieve an article by permalink', () => {

                $httpBackend.expectGET('/api/articles/'+mockArticle.permalink).respond(mockArticle);

                let article = articleService.getArticle(mockArticle.permalink);

                expect(article).eventually.to.be.fulfilled;
                expect(article).eventually.to.deep.equal(mockArticle);

                $httpBackend.flush();

            });

        });

        describe('New Article', () => {

            it('should be able to get a new article with a UUID', () => {

                let article = articleService.newArticle();

                expect(article.articleId).to.be.ok;

            });

        });

        describe('Save Article', () => {


            it('should save a new article and all related entities', () => {

                let article = fixtures.getArticle();

                $httpBackend.expectPUT('/api/articles/'+article.articleId, article.getAttributes()).respond(201);
                $httpBackend.expectPUT('/api/articles/'+article.articleId+'/tags', _.clone(article._tags, true)).respond(201);
                $httpBackend.expectPUT('/api/articles/'+article.articleId+'/meta', _.clone(article._articleMetas, true)).respond(201);

                let savePromise = articleService.saveArticleWithRelated(article);

                expect(savePromise).eventually.to.be.fulfilled;
                expect(savePromise).eventually.to.deep.equal(article);

                $httpBackend.flush();

            });


            it('should save an existing article with a patch request', () => {

                let article = fixtures.getArticle();
                article.setExists(true);

                article.title = "This title has been updated";

                let newTag = new common.models.Tag({
                    tagId: seededChance.guid(),
                    tag: "new tag",
                });

                article._tags = [newTag];

                $httpBackend.expectPATCH('/api/articles/'+article.articleId, (<common.decorators.IChangeAwareDecorator>article).getChanged()).respond(201);
                $httpBackend.expectPUT('/api/articles/'+article.articleId+'/tags', _.clone(article._tags, true)).respond(201);

                let savePromise = articleService.saveArticleWithRelated(article);

                expect(savePromise).eventually.to.be.fulfilled;
                expect(savePromise).eventually.to.deep.equal(article);

                $httpBackend.flush();

            });

        });

        describe('Meta tag hydration', () => {

            let articleMetaTemplate:string[] = [
                'name', 'description', 'keyword', 'canonical'
            ];

            it('should be able to hydrate meta tags from a template', () => {

                let article = fixtures.getArticle();

                let hydratedMetaTags = articleService.hydrateMetaFromTemplate(article, articleMetaTemplate);

                expect(_.size(hydratedMetaTags)).to.equal(5);

                expect(hydratedMetaTags[0].articleId).to.equal(article.articleId);

                expect(_.isEmpty(hydratedMetaTags[0].id)).to.be.false;

                let testableMetaTags = _.cloneDeep(hydratedMetaTags);
                _.forEach(testableMetaTags, (tag) => {
                    delete(tag.id);
                    delete(tag.articleId);
                });

                expect(testableMetaTags).to.deep.equal([
                    {
                        metaName: 'name',
                        metaContent: ''
                    },
                    {
                        metaName: 'description',
                        metaContent: 'bar'
                    },
                    {
                        metaName: 'keyword',
                        metaContent: 'foo'
                    },
                    {
                        metaName: 'canonical',
                        metaContent: ''
                    },
                    {
                        metaName: 'foobar',
                        metaContent: 'foobar'
                    }
                ]);

            });

        });

    });

})();