<!doctype html>
<html lang="en">
  <head>
    <script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.js" crossorigin="anonymous"></script>
    <script>/*window.jQuery || document.write('<script src="{{$themepath}}/js/vendor/jquery-slim.min.js"><\/script>')*/</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="{{$favicon}}">

    <title>{{$sitename}}</title>

    <!-- Bootstrap core CSS -->
    <link href="{{$themepath}}/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{$themepath}}/main.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="{{$themepath}}/sticky-footer-navbar.css" rel="stylesheet">
  </head>
  {{$style}}
<style>

.bg-dark {
background-color:{{$menubg}};
color: {{$menufg}};
}
.navbar-dark .navbar-nav .nav-link {
    color: {{$menufg}};
}
.footer {
background-color:{{$footerbg}};
color: {{$footerfg}};
}
.news-title {
background-color:{{$newstitlebg}};
color: {{$newstitlefg}};
}
.story-odd {
background-color:{{$storyoddbg}};
color: {{$storyoddfg}};
}
.story-even {
background-color:{{$storyevenbg}};
color: {{$storyevenfg}};
}
body {
background-color:{{$pagebg}};
color:{{$pagefg}}
}

.topheader {
    padding-top: 60px;
    min-height: 60px;
    margin-bottom: 30px;
}
</style>

  <body>

    <header>
      <!-- Fixed navbar -->
      <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <a class="navbar-brand" href="/">{{$logoblock}}</a>
<a class="navbar-brand" href="/">{{$sitename}}</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
          <ul class="navbar-nav mr-auto">
{{$menu}}
          </ul>
{{$menurightfloat}}

<!--
          <form class="form-inline mt-2 mt-md-0">
            <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
          </form>
-->
        </div>
      </nav>
    </header>

    <!-- Begin page content -->
    <main role="main" class="container">
{{$topheader}}
{{$beforecontent}}
<div id="content">
{{$content}}
</div>
<div id="aftecontent">
{{$aftercontent}}
</div>
</div>
    </main>

    <footer id="pagefooter" class="footer">
      <div class="container">
{{$footer}}
      </div>
    </footer>


    <script src="{{$themepath}}/popper.min.js"></script>
    <script src="{{$themepath}}/js/bootstrap.min.js"></script>
  </body>
</html>

