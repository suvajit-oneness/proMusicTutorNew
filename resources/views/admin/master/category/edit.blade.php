@extends('layouts.auth.authMaster')
@section('title','Edit Category')
@section('content')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Category
                        <a class="headerbuttonforAdd" href="{{route('admin.master.category.list')}}"><i class="fa fa-step-backward" aria-hidden="true"></i>BACK</a>
                    </h5>
                    <!-- <p>This example shows FixedHeader being styled by the Bootstrap 4 CSS framework.</p> -->
                </div>
                <div class="card-body">
                    <form method="post" action="{{route('admin.master.category.update',$category->id)}}" enctype="multipart/form-data" class="row">
                        @csrf
                        <input type="hidden" name="categoryId" value="{{$category->id}}">
                        @error('categoryId')
                            <span class="text-danger" role="alert">{{ $message }}</span>
                        @enderror
                        <div class="form-group image-upload-wrapper col-lg-6">
                            <img src="{{asset($category->image)}}" height="auto" width="100">
                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image">
                            <button><i class="fas fa-pencil-alt"></i></button>
                            @error('image')
                                <span class="text-danger" role="alert">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="instrument" class="col-form-label">Instrument:</label>
                                <select class="form-control @error('image') is-invalid @enderror" name="instrument" id="instrument">
                                    <option value="" selected="" hidden="">Select Instrument</option>
                                    @foreach($instrument as $key => $ins)
                                        <option value="{{$ins->id}}" @if((old('instrument') ? old('instrument') : $category->instrumentId) == $ins->id){{('selected')}}@endif>{{$ins->name}}</option>
                                    @endforeach
                                </select>
                                @error('instrument')
                                    <span class="text-danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="image" class="col-form-label">Instrument Name:</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="image" name="name" value="{{$category->name}}" placeholder="Instrument Name">
                                @error('name')
                                    <span class="text-danger" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="form-group col-lg-12">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@section('script')
    <script type="text/javascript">
        
    </script>
@stop
@endsection